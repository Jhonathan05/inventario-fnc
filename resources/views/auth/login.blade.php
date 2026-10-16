@extends('layouts.auth')

@section('title', 'Acceso Institucional')

@php
    $keycloakEnabled = config('keycloak.enabled');
    $localLoginEnabled = config('auth.local_login_enabled');

    $keycloakErrors = [
        'keycloak_state' => 'La verificación de seguridad no coincide. Intenta iniciar sesión nuevamente.',
        'keycloak_code' => 'Keycloak no devolvió un código de autorización válido.',
        'keycloak_token' => 'No fue posible validar la sesión con Keycloak. Intenta nuevamente.',
        'keycloak_nonce' => 'La verificación de identidad no coincide. Intenta iniciar sesión nuevamente.',
        'keycloak_email' => 'Keycloak no devolvió un correo institucional válido.',
        'keycloak_unreachable' => 'El servicio de identidad institucional no está disponible en este momento.',
    ];
    $keycloakError = request('error') === 'keycloak'
        ? ($keycloakErrors[request('reason')] ?? null)
        : null;
@endphp

@section('content')
<div class="login-split-container" data-app="inventario">
    {{-- Panel izquierdo: Hero Institucional (55%) --}}
    <div class="login-hero-panel">
        <div class="login-hero-decor">
            <div class="decor-circle circle-1"></div>
            <div class="decor-circle circle-2"></div>
            <div class="decor-circle circle-3"></div>
        </div>
        <div class="login-hero-content">
            <span class="hero-tag">Sistema Institucional</span>
            <h1 class="hero-title">Sistema de<br>Inventario</h1>
            <p class="hero-desc">
                Comité Departamental de Cafeteros del Tolima — Plataforma de registro
                y control de equipos tecnológicos.
            </p>
        </div>
    </div>

    {{-- Panel derecho: firma corporativa (45%) --}}
    <div class="login-form-panel">
        <div class="login-form-wrapper">
            <img src="{{ asset('imagenes/logo-fnc-tolima.png') }}" alt="Logo FNC" class="login-logo">
            <h2 class="login-title">Acceso Institucional</h2>
            <p class="login-subtitle">Ingresa con tu cuenta del Comité Tolima</p>

            @if ($keycloakError)
                <div class="login-status-banner login-status-banner--aviso" style="display:block; margin-bottom:18px;">
                    {{ $keycloakError }}
                </div>
            @endif

            @if (! $keycloakEnabled)
                <div class="login-status-banner login-status-banner--aviso" style="display:block; margin-bottom:18px;">
                    El acceso institucional no está configurado. Define <code>KEYCLOAK_*</code>
                    o habilita el modo mock (<code>MOCK_AUTH=true</code>) para desarrollo.
                </div>
            @endif

            {{-- Opción B del canon: botón único de SSO institucional --}}
            <a href="{{ route('auth.keycloak') }}" class="btn-login" id="btn-keycloak"
               @if (! $keycloakEnabled) aria-disabled="true" style="pointer-events:none; opacity:.6;" @endif>
                <i data-lucide="shield-check"></i>
                Continuar con Comité Tolima
            </a>

            @if ($localLoginEnabled)
                {{-- Formulario local: sólo con LOCAL_LOGIN_ENABLED=true (transición/soporte) --}}
                <form id="form-login" method="POST" action="{{ route('login') }}" novalidate style="margin-top:28px;">
                    @csrf
                    <input type="hidden" name="_tab" value="{{ request('_tab') }}">

                    <div class="form-group premium-input-group">
                        <div class="premium-input">
                            <div class="email-icon"><i data-lucide="mail"></i></div>
                            <div class="input-content">
                                <input type="email" id="login-user" name="email" class="premium-email"
                                       placeholder=" " value="{{ old('email') }}" required autocomplete="username">
                                <label class="floating-label" for="login-user">Correo Electrónico Institucional</label>
                            </div>
                            <button type="button" class="action-btn" id="btn-clear-email" style="display: none;" aria-label="Limpiar correo">
                                <i data-lucide="x"></i>
                            </button>
                        </div>

                        <div class="quick-select">
                            <div class="domain-list">
                                <button type="button" class="domain-btn" data-domain="cafedecolombia.com">
                                    <img src="{{ asset('branding/logo-fnc.svg') }}" alt="FNC">
                                    cafedecolombia.com
                                </button>
                                <button type="button" class="domain-btn" data-domain="cafedecolombia.com.co">
                                    <img src="{{ asset('branding/logo-fnc.svg') }}" alt="FNC">
                                    cafedecolombia.com.co
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group premium-input-group">
                        <div class="premium-input">
                            <div class="email-icon"><i data-lucide="lock"></i></div>
                            <div class="input-content">
                                <input type="password" id="login-pass" name="password" class="premium-email"
                                       placeholder=" " required autocomplete="current-password">
                                <label class="floating-label" for="login-pass">Contraseña</label>
                            </div>
                            <button type="button" class="action-btn" id="btn-toggle-password" aria-label="Ver contraseña">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="login-status-banner login-status-banner--aviso" style="display:block; margin-bottom:16px;">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button type="submit" class="btn-login" id="btn-submit-login">Iniciar Sesión</button>
                </form>
            @endif

            <div id="login-status" class="login-status-banner" style="display:none;"></div>

            <div class="login-footer">
                <button type="button" id="btn-legal-modal" class="btn-link">Tratamiento de Datos Personales</button>
                <p class="footer-credit">Federación Nacional de Cafeteros de Colombia</p>

                @if (config('mock.enabled'))
                    <p class="footer-credit">
                        <a href="{{ route('mock.login') }}" style="color: var(--primary); font-weight: 600;">
                            Modo mock (desarrollo)
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@include('auth.partials.legal-modal')
@endsection

@push('head')
<script>
    // El canon intercepta el submit de #form-login; aquí se delega al POST nativo.
    window.FNC_LOGIN_OPTIONS = {
        onSubmit: function () {
            const form = document.getElementById('form-login');
            if (form) form.submit();
        },
    };
</script>
@endpush
