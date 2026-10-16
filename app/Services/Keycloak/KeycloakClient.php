<?php

namespace App\Services\Keycloak;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente OIDC mínimo para Keycloak (Authorization Code + PKCE S256).
 *
 * Sin dependencias externas: descubrimiento, exchange de código y lectura de
 * claims/roles con el cliente HTTP de Laravel. El id_token se valida contra el
 * canal de servidor (token endpoint vía backchannel TLS) y su `nonce`; no se
 * re-verifica la firma RS256 localmente para no arrastrar librerías JWT.
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

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return [];
        }

        return (array) (json_decode($decoded, true) ?: []);
    }

    /**
     * URL de cierre de sesión (RP-initiated logout).
     */
    public function endSessionUrl(?string $idToken, string $postLogoutRedirect): ?string
    {
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
