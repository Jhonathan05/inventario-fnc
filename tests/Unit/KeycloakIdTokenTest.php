<?php

namespace Tests\Unit;

use App\Services\Keycloak\KeycloakClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Verificación local del `id_token` (firma RS256 contra JWKS + claims).
 *
 * Las claves se generan en memoria y el JWKS se inyecta, así que no hay red.
 */
class KeycloakIdTokenTest extends TestCase
{
    private const ISSUER = 'https://kc.example.test/auth/realms/fnc-realm';

    private const CLIENT_ID = 'inventario-fnc';

    private const KID = 'test-key-1';

    private static \OpenSSLAsymmetricKey $privateKey;

    /** @var array<int, array<string, mixed>> */
    private static array $jwks = [];

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            self::markTestSkipped('OpenSSL no pudo generar la clave RSA de prueba.');
        }

        self::$privateKey = $key;
        $details = openssl_pkey_get_details($key);

        self::$jwks = [[
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => self::KID,
            'n' => self::base64Url($details['rsa']['n']),
            'e' => self::base64Url($details['rsa']['e']),
        ]];
    }

    public function test_acepta_un_id_token_rs256_valido(): void
    {
        $claims = $this->client()->verifyIdToken($this->token(), 'nonce-abc', self::$jwks);

        $this->assertSame('juan.perez@cafedecolombia.com', $claims['email']);
        $this->assertSame(self::CLIENT_ID, $claims['aud']);
    }

    public function test_rechaza_una_firma_de_otra_clave(): void
    {
        $otra = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken($this->token(key: $otra), 'nonce-abc', self::$jwks);
    }

    public function test_rechaza_alg_none(): void
    {
        $now = time();

        $header = self::base64Url(json_encode(['alg' => 'none', 'typ' => 'JWT']));
        $payload = self::base64Url(json_encode([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'nonce' => 'nonce-abc',
            'exp' => $now + 300,
        ]));

        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken($header.'.'.$payload.'.', 'nonce-abc', self::$jwks);
    }

    public function test_rechaza_un_kid_desconocido(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken(
            $this->token(headerOverrides: ['kid' => 'kid-inexistente']),
            'nonce-abc',
            self::$jwks,
        );
    }

    public function test_rechaza_un_token_expirado(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken(
            $this->token(['exp' => time() - 3600]),
            'nonce-abc',
            self::$jwks,
        );
    }

    public function test_rechaza_un_token_del_futuro(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken(
            $this->token(['nbf' => time() + 3600]),
            'nonce-abc',
            self::$jwks,
        );
    }

    public function test_rechaza_un_emisor_distinto(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken(
            $this->token(['iss' => 'https://otro.example.test/realms/x']),
            'nonce-abc',
            self::$jwks,
        );
    }

    public function test_rechaza_una_audiencia_distinta(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken(
            $this->token(['aud' => 'otro-cliente']),
            'nonce-abc',
            self::$jwks,
        );
    }

    public function test_rechaza_un_nonce_que_no_coincide(): void
    {
        $this->expectException(RuntimeException::class);

        $this->client()->verifyIdToken($this->token(), 'nonce-distinto', self::$jwks);
    }

    public function test_acepta_aud_como_arreglo_con_azp(): void
    {
        $claims = $this->client()->verifyIdToken(
            $this->token(['aud' => [self::CLIENT_ID, 'otro-cliente'], 'azp' => self::CLIENT_ID]),
            'nonce-abc',
            self::$jwks,
        );

        $this->assertSame(self::CLIENT_ID, $claims['azp']);
    }

    /**
     * @param  array<string, mixed>  $claimsOverrides
     * @param  array<string, mixed>|null  $headerOverrides
     */
    private function token(
        array $claimsOverrides = [],
        ?array $headerOverrides = null,
        ?\OpenSSLAsymmetricKey $key = null,
    ): string {
        $now = time();

        $header = array_merge(
            ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::KID],
            $headerOverrides ?? [],
        );

        $claims = array_merge([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => 'usuario-123',
            'email' => 'juan.perez@cafedecolombia.com',
            'name' => 'Juan Pérez',
            'nonce' => 'nonce-abc',
            'iat' => $now,
            'exp' => $now + 300,
        ], $claimsOverrides);

        $signingInput = self::base64Url(json_encode($header)).'.'.self::base64Url(json_encode($claims));

        $signature = '';
        openssl_sign($signingInput, $signature, $key ?? self::$privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput.'.'.self::base64Url($signature);
    }

    private function client(): KeycloakClient
    {
        return new KeycloakClient([
            'enabled' => true,
            'client_id' => self::CLIENT_ID,
            'realm' => 'fnc-realm',
            'base_url' => 'https://kc.example.test/auth',
            'public_base_url' => 'https://kc.example.test/auth',
            'scopes' => 'openid profile email',
            'discovery_ttl' => 30,
            'timeout' => 5,
            'leeway' => 60,
        ]);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
