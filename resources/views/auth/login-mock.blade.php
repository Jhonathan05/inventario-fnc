@extends('layouts.auth')

@section('title', 'Selector de perfiles (mock)')

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
            <span class="hero-tag">Modo Mock — Desarrollo</span>
            <h1 class="hero-title">Sistema de<br>Inventario</h1>
            <p class="hero-desc">
                Comité Departamental de Cafeteros del Tolima — Plataforma de registro
                y control de equipos tecnológicos.
            </p>
        </div>
    </div>

    {{-- Panel derecho: selector de perfiles sintéticos --}}
    <div class="login-form-panel">
        <div class="login-form-wrapper">
            <img src="{{ asset('imagenes/logo-fnc-tolima.png') }}" alt="Logo FNC" class="login-logo">
            <h2 class="login-title">Acceso Institucional</h2>
            <p class="login-subtitle">Selecciona un perfil de prueba para ingresar</p>

            <div class="mock-badge">
                <i data-lucide="flask-conical"></i>
                <span>MODO MOCK — sesiones sintéticas sin Keycloak</span>
            </div>

            <div class="mock-profiles">
                @foreach ($profiles as $profile)
                    <form method="POST" action="{{ route('mock.login.store') }}" class="mock-profile-form">
                        @csrf
                        <input type="hidden" name="_tab" value="{{ request('_tab') }}">
                        <input type="hidden" name="role" value="{{ $profile['key'] }}">

                        <button type="submit" class="mock-profile-card" data-mock-role="{{ $profile['role'] }}">
                            <span class="mock-profile-head">
                                <span class="mock-profile-role">{{ $profile['role'] }}</span>
                                <i data-lucide="log-in"></i>
                            </span>
                            <span class="mock-profile-name">{{ $profile['nombre'] }}</span>
                            <span class="mock-profile-desc">{{ $profile['descripcion'] }}</span>
                            <span class="mock-profile-mail">{{ $profile['email'] }}</span>
                        </button>
                    </form>
                @endforeach
            </div>

            <div id="login-status" class="login-status-banner" style="display:none;"></div>

            <div class="login-footer">
                <button type="button" id="btn-legal-modal" class="btn-link">Tratamiento de Datos Personales</button>
                <p class="footer-credit">Federación Nacional de Cafeteros de Colombia</p>
            </div>
        </div>
    </div>
</div>

@include('auth.partials.legal-modal')
@endsection

@push('head')
<style>
    /* Estilos propios del selector mock (no forman parte de la firma canon). */
    .mock-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--primary-tint);
        color: var(--primary);
        border: 1px solid rgba(22, 24, 29, 0.2);
        border-radius: var(--radius-md);
        padding: 10px 14px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.35px;
        line-height: 1.35;
        text-align: left;
        text-transform: uppercase;
        margin-bottom: 22px;
    }

    .mock-badge svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .mock-profiles {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .mock-profile-card {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 4px;
        text-align: left;
        background: #ffffff;
        border: 1.5px solid var(--border-light);
        border-radius: 14px;
        padding: 16px 18px;
        cursor: pointer;
        font-family: var(--font-family);
        transition: border-color 0.25s, box-shadow 0.25s, transform 0.25s;
    }

    .mock-profile-card:hover {
        border-color: var(--focus);
        box-shadow: 0 8px 24px var(--primary-tint);
        transform: translateY(-1px);
    }

    .mock-profile-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mock-profile-head svg {
        width: 16px;
        height: 16px;
        color: var(--primary);
    }

    .mock-profile-role {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--primary);
    }

    .mock-profile-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    .mock-profile-desc {
        font-size: 0.82rem;
        color: var(--text-muted);
        line-height: 1.45;
    }

    .mock-profile-mail {
        font-size: 0.75rem;
        color: var(--outline-variant);
        margin-top: 4px;
    }
</style>
@endpush
