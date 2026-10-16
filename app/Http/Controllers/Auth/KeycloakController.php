<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\IdentityProvisioner;
use App\Services\Keycloak\KeycloakClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Login OIDC institucional contra Keycloak (skill fnc-keycloak-oidc-agenda).
 *
 * Authorization Code + PKCE (S256) con `state`/`nonce`/`code_verifier` en
 * cookies httpOnly de 15 min (cubren el enrolamiento OTP de Keycloak).
 * El parámetro `_tab` viaja dentro del `redirect_uri` para que la sesión se
 * resuelva siempre en el bucket de pestaña correcto (TabSessionGuard).
 */
class KeycloakController extends Controller
{
    /** Minutos de vida de las cookies kc_* (con 5 min expiraban a mitad del flujo). */
    private const COOKIE_TTL = 15;

    /** Minutos de vida de la cookie con el id_token (para el RP logout). */
    private const ID_TOKEN_TTL = 60;

    public function __construct(
        private readonly KeycloakClient $keycloak,
        private readonly IdentityProvisioner $provisioner,
    ) {
    }

    /**
     * Inicia el flujo: guarda state/nonce/verifier y redirige a Keycloak.
     */
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->keycloak->enabled(), 404, 'El acceso institucional no está configurado.');

        $tab = $this->tab($request);

        $state = Str::random(40);
        $nonce = Str::random(32);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        try {
            $url = $this->keycloak->authorizationUrl([
                'state' => $state,
                'nonce' => $nonce,
                'code_challenge' => $challenge,
                'redirect_uri' => $this->callbackUrl($tab),
                'action' => $request->query('action'),
            ]);
        } catch (RuntimeException) {
            return $this->fail($request, 'keycloak_unreachable');
        }

        $seconds = self::COOKIE_TTL * 60;

        return redirect()->away($url)
            ->withCookie($this->stateCookie($request, 'kc_state', $state, $seconds))
            ->withCookie($this->stateCookie($request, 'kc_nonce', $nonce, $seconds))
            ->withCookie($this->stateCookie($request, 'kc_verifier', $verifier, $seconds));
    }

    /**
     * Callback OIDC: valida state, intercambia el código, resuelve el rol y
     * autentica al usuario local.
     */
    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->keycloak->enabled(), 404, 'El acceso institucional no está configurado.');

        // El usuario canceló explícitamente en Keycloak: no es un error del sistema.
        if (in_array($request->query('error'), ['access_denied', 'cancelled'], true)) {
            return $this->fail($request, null);
        }

        $state = $request->cookie('kc_state');
        $nonce = $request->cookie('kc_nonce');
        $verifier = $request->cookie('kc_verifier');

        if (! $state || ! hash_equals((string) $state, (string) $request->query('state'))) {
            return $this->fail($request, 'keycloak_state');
        }

        if (! $request->query('code')) {
            return $this->fail($request, 'keycloak_code');
        }

        try {
            $tokens = $this->keycloak->exchangeCode(
                (string) $request->query('code'),
                $verifier ? (string) $verifier : null,
                $this->callbackUrl($this->tab($request)),
            );
        } catch (RuntimeException) {
            return $this->fail($request, 'keycloak_token');
        }

        $claims = $this->keycloak->decodeJwt((string) ($tokens['id_token'] ?? ''));

        if ($nonce && isset($claims['nonce']) && ! hash_equals((string) $nonce, (string) $claims['nonce'])) {
            return $this->fail($request, 'keycloak_nonce');
        }

        $email = $claims['email'] ?? $claims['preferred_username'] ?? null;

        if (! is_string($email) || ! str_contains($email, '@')) {
            return $this->fail($request, 'keycloak_email');
        }

        $role = $this->keycloak->localRole($this->keycloak->rolesFromTokens($tokens));
        $user = $this->provisioner->provision($email, $claims['name'] ?? null, $role);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))
            ->withCookie(cookie()->forget('kc_state'))
            ->withCookie(cookie()->forget('kc_nonce'))
            ->withCookie(cookie()->forget('kc_verifier'))
            ->withCookie($this->stateCookie(
                $request,
                'kc_id_token',
                (string) ($tokens['id_token'] ?? ''),
                self::ID_TOKEN_TTL * 60,
            ));
    }

    /**
     * Volver al login tras un fallo (nunca a una ruta que rebote a Keycloak:
     * evita bucles ERR_TOO_MANY_REDIRECTS).
     */
    private function fail(Request $request, ?string $reason): RedirectResponse
    {
        $params = ['error' => 'keycloak'];

        if ($reason !== null) {
            $params['reason'] = $reason;
        }

        return redirect()->route('login', $params)
            ->withCookie(cookie()->forget('kc_state'))
            ->withCookie(cookie()->forget('kc_nonce'))
            ->withCookie(cookie()->forget('kc_verifier'));
    }

    private function tab(Request $request): ?string
    {
        $tab = $request->input('_tab') ?: $request->query('_tab');

        return is_string($tab) && $tab !== '' ? $tab : null;
    }

    private function callbackUrl(?string $tab): string
    {
        $url = route('auth.keycloak.callback');

        if ($tab) {
            $url .= (str_contains($url, '?') ? '&' : '?').'_tab='.urlencode($tab);
        }

        return $url;
    }

    private function stateCookie(Request $request, string $name, string $value, int $seconds)
    {
        return cookie(
            $name,
            $value,
            intdiv($seconds, 60),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }
}
