<?php

namespace App\Services\Keycloak;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente OIDC mínimo para Keycloak (Authorization Code + PKCE S256).
 *
 * Sin dependencias externas: descubrimiento, exchange de código, verificación
 * de firma RS256 del `id_token` contra el JWKS del realm (OpenSSL) y lectura de
 * claims/roles con el cliente HTTP de Laravel.
 */
class KeycloakClient
{
    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfig(): self
    {
        return new self((array) config('keycloak', []));
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false) && ! empty($this->config['client_id']);
    }

    /**
     * URL del emisor (*público*, para que coincida `id_token.iss`).
     */
    public function issuer(): string
    {
        return $this->realmUrl($this->config['public_base_url'] ?? '');
    }

    /**
     * Documento de descubrimiento OIDC (cacheado 30 min).
     *
     * @return array<string, mixed>
     */
    public function discovery(): array
    {
        $url = $this->realmUrl($this->config['base_url'] ?? '').'/.well-known/openid-configuration';
        $ttl = (int) ($this->config['discovery_ttl'] ?? 30);

        return Cache::remember('keycloak.discovery.'.sha1($url), now()->addMinutes($ttl), function () use ($url) {
            $response = Http::timeout($this->timeout())->acceptJson()->get($url);

            if ($response->failed()) {
                throw new RuntimeException('No fue posible obtener la configuración OIDC de Keycloak.');
            }

            return (array) $response->json();
        });
    }

    /**
     * Construye la URL de autorización (frontchannel).
     *
     * @param  array{state:string, nonce:string, code_challenge:string, redirect_uri:string, prompt?:string|null, action?:string|null} $params
     */
    public function authorizationUrl(array $params): string
    {
        $discovery = $this->discovery();
        $endpoint = $discovery['authorization_endpoint']
            ?? throw new RuntimeException('Keycloak no expone authorization_endpoint.');

        $query = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $params['redirect_uri'],
            'response_type' => 'code',
            'scope' => $this->config['scopes'] ?? 'openid profile email',
            'state' => $params['state'],
            'nonce' => $params['nonce'],
            'code_challenge' => $params['code_challenge'],
            'code_challenge_method' => 'S256',
        ];

        // Nunca `prompt=none` (la spec OIDC prohíbe renderizar UI y Keycloak
        // aborta con interaction_required). Con kc_action se omite `prompt`.
        if (! empty($params['action'])) {
            $query['kc_action'] = $params['action'];
        } else {
            $query['prompt'] = $params['prompt'] ?? 'login';
        }

        return $endpoint.'?'.http_build_query($query);
    }

    /**
     * Intercambia el `code` por tokens (backchannel).
     *
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code, ?string $codeVerifier, string $redirectUri): array
    {
        $discovery = $this->discovery();
        $endpoint = $discovery['token_endpoint']
            ?? throw new RuntimeException('Keycloak no expone token_endpoint.');

        $response = Http::asForm()->timeout($this->timeout())->post($endpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('El intercambio de código OIDC falló.');
        }

        return (array) $response->json();
    }

    /**
     * Extrae los roles FNC: primero client roles aislados por app
     * (`resource_access[clientId]`), con fallback a `realm_access`.
     *
     * @param  array<string, mixed> $tokens
     * @return array<int, string>
     */
    public function rolesFromTokens(array $tokens): array
    {
        $clientId = (string) ($this->config['client_id'] ?? '');

        $payloads = [
            $this->decodeJwt((string) ($tokens['access_token'] ?? '')),
            $this->decodeJwt((string) ($tokens['id_token'] ?? '')),
        ];

        $roles = [];
        foreach ($payloads as $payload) {
            foreach ((array) ($payload['resource_access'][$clientId]['roles'] ?? []) as $role) {
                $roles[] = (string) $role;
            }
        }

        if ($roles === []) {
            foreach ($payloads as $payload) {
                foreach ((array) ($payload['realm_access']['roles'] ?? []) as $role) {
                    $roles[] = (string) $role;
                }
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Traduce los roles Keycloak al rol local de mayor jerarquía.
     *
     * @param  array<int, string> $keycloakRoles
     */
    public function localRole(array $keycloakRoles): ?string
    {
        $hierarchy = (array) ($this->config['role_hierarchy'] ?? []);
        $map = (array) ($this->config['role_map'] ?? []);
        $default = $this->config['default_role'] ?? null;

        $best = null;
        $bestWeight = -1;

        foreach ($keycloakRoles as $role) {
            $weight = $hierarchy[$role] ?? -1;

            if ($weight > $bestWeight) {
                $bestWeight = $weight;
                $best = $role;
            }
        }

        if ($best === null) {
            return $default;
        }

        return $map[$best] ?? $default;
    }

    /**
     * Decodifica el payload de un JWT (sin verificar firma).
     *
     * @return array<string, mixed>
     */
    public function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            return [];
        }

        return (array) (json_decode($this->base64UrlDecode($parts[1]), true) ?: []);
    }

    /**
     * JWKS del realm (claves públicas de firma), cacheado como el discovery.
     *
     * @return array<int, array<string, mixed>>
     */
    public function jwks(): array
    {
        $discovery = $this->discovery();
        $uri = $discovery['jwks_uri'] ?? null;

        if (! is_string($uri) || $uri === '') {
            throw new RuntimeException('Keycloak no expone jwks_uri.');
        }

        $ttl = (int) ($this->config['discovery_ttl'] ?? 30);

        return (array) Cache::remember('keycloak.jwks.'.sha1($uri), now()->addMinutes($ttl), function () use ($uri) {
            $response = Http::timeout($this->timeout())->acceptJson()->get($uri);

            if ($response->failed()) {
                throw new RuntimeException('No fue posible obtener el JWKS de Keycloak.');
            }

            return (array) $response->json('keys', []);
        });
    }

    /**
     * Verifica un `id_token` OIDC: firma RS256 contra el JWKS del realm y sus
     * claims (iss, aud, exp/nbf y nonce).
     *
     * Falla cerrado: cualquier anomalía lanza RuntimeException, de modo que el
     * llamador deniega el acceso en lugar de confiar en un token sin validar.
     *
     * @param  array<int, array<string, mixed>>|null  $jwks  JWKS explícito (tests); por defecto se descarga.
     * @return array<string, mixed> claims verificadas
     */
    public function verifyIdToken(string $idToken, ?string $expectedNonce = null, ?array $jwks = null): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new RuntimeException('El id_token está malformado.');
        }

        $header = (array) (json_decode($this->base64UrlDecode($parts[0]), true) ?: []);
        $payload = (array) (json_decode($this->base64UrlDecode($parts[1]), true) ?: []);

        // Sólo RS256: descarta `alg: none` y ataques de confusión de algoritmo.
        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('El id_token no usa RS256 (algoritmo no permitido).');
        }

        $key = $this->selectJwk($jwks ?? $this->jwks(), $header['kid'] ?? null);

        if ($key === null) {
            throw new RuntimeException('No hay una clave de firma válida (kid) en el JWKS del realm.');
        }

        $verified = openssl_verify(
            $parts[0].'.'.$parts[1],
            $this->base64UrlDecode($parts[2]),
            $this->jwkToPem($key),
            OPENSSL_ALGO_SHA256,
        );

        if ($verified !== 1) {
            throw new RuntimeException('La firma del id_token no es válida.');
        }

        $this->assertIdTokenClaims($payload, $expectedNonce);

        return $payload;
    }

    /**
     * Elige la clave RSA de firma del JWKS (por `kid` si el token lo trae).
     *
     * @param  array<int, mixed>  $keys
     * @return array<string, mixed>|null
     */
    private function selectJwk(array $keys, ?string $kid): ?array
    {
        foreach ($keys as $key) {
            if (! is_array($key) || ($key['kty'] ?? null) !== 'RSA') {
                continue;
            }

            if (isset($key['use']) && $key['use'] !== 'sig') {
                continue;
            }

            if ($kid !== null && ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            if (isset($key['n'], $key['e'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Valida `iss`, `aud`, `exp`/`nbf` y (si se espera) `nonce`.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertIdTokenClaims(array $payload, ?string $expectedNonce): void
    {
        $issuer = $this->issuer();

        if ($issuer !== '' && ($payload['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('El emisor (iss) del id_token no es el esperado.');
        }

        $clientId = (string) ($this->config['client_id'] ?? '');

        if ($clientId !== '') {
            $audiences = array_map('strval', (array) ($payload['aud'] ?? []));

            if (! in_array($clientId, $audiences, true)) {
                throw new RuntimeException('La audiencia (aud) del id_token no corresponde al cliente.');
            }
        }

        $leeway = max(0, (int) ($this->config['leeway'] ?? 60));
        $now = time();

        if (isset($payload['exp']) && $now > ((int) $payload['exp']) + $leeway) {
            throw new RuntimeException('El id_token está expirado.');
        }

        if (isset($payload['nbf']) && $now + $leeway < (int) $payload['nbf']) {
            throw new RuntimeException('El id_token todavía no es válido (nbf).');
        }

        if ($expectedNonce !== null && ! hash_equals($expectedNonce, (string) ($payload['nonce'] ?? ''))) {
            throw new RuntimeException('El nonce del id_token no coincide (posible replay).');
        }
    }

    /**
     * Convierte una clave pública JWK (RSA) al PEM SubjectPublicKeyInfo.
     *
     * @param  array<string, mixed>  $jwk
     */
    private function jwkToPem(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) ($jwk['n'] ?? ''));
        $exponent = $this->base64UrlDecode((string) ($jwk['e'] ?? ''));

        if ($modulus === '' || $exponent === '') {
            throw new RuntimeException('La clave del JWKS está incompleta (n/e).');
        }

        // RSAPublicKey ::= SEQUENCE { modulus INTEGER, publicExponent INTEGER }
        $rsaKey = $this->derSequence($this->derInteger($modulus).$this->derInteger($exponent));

        // AlgorithmIdentifier para rsaEncryption (OID 1.2.840.113549.1.1.1 + NULL).
        $algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        // SubjectPublicKeyInfo ::= SEQUENCE { algorithm, BIT STRING }
        $spki = $this->derSequence($algorithm.$this->derBitString($rsaKey));

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($spki), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function derSequence(string $content): string
    {
        return "\x30".$this->derLength(strlen($content)).$content;
    }

    private function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");

        if ($bytes === '') {
            $bytes = "\x00";
        }

        // El INTEGER DER es con signo: si el bit alto está a 1, se antepone 0x00.
        if (ord($bytes[0]) > 0x7f) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".$this->derLength(strlen($bytes)).$bytes;
    }

    private function derBitString(string $content): string
    {
        // El BIT STRING lleva un byte inicial con el número de bits sin usar.
        return "\x03".$this->derLength(strlen($content) + 1)."\x00".$content;
    }

    private function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes = chr($length & 0xff).$bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        $decoded = base64_decode($value, true);

        return $decoded === false ? '' : $decoded;
    }

    /**
     * URL de cierre de sesión (RP-initiated logout).
     */
    public function endSessionUrl(?string $idToken, string $postLogoutRedirect): ?string
    {
        // Sin Keycloak configurado no hay cierre SSO que hacer (y así el logout
        // local no intenta contactar un servidor inexistente, p. ej. en tests).
        if (! $this->enabled()) {
            return null;
        }

        try {
            $discovery = $this->discovery();
        } catch (RuntimeException) {
            return null;
        }

        $endpoint = $discovery['end_session_endpoint'] ?? null;

        if ($endpoint === null) {
            return null;
        }

        $query = ['post_logout_redirect_uri' => $postLogoutRedirect];

        if ($idToken) {
            $query['id_token_hint'] = $idToken;
        }

        return $endpoint.'?'.http_build_query($query);
    }

    private function realmUrl(string $base): string
    {
        return rtrim($base, '/').'/realms/'.($this->config['realm'] ?? '');
    }

    private function timeout(): int
    {
        return max(1, (int) ($this->config['timeout'] ?? 10));
    }
}
