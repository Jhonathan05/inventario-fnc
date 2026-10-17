@php
    /*
     * Página 403 autocontenida.
     *
     * Antes extendía `layouts.inventario`, que es un layout AUTENTICADO: al
     * renderizar un 403 para un invitado (p. ej. POST /login con el login local
     * deshabilitado) intentaba resolver `auth()->user()` y `route('usuarios.edit')`
     * y terminaba en un 500. Aquí no se depende de la sesión.
     */
    $previous = url()->previous();
    $current = url()->current();

    $backUrl = $previous !== $current
        ? $previous
        : (auth()->check() ? route('dashboard') : route('login'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app="inventario">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado | {{ config('app.name', 'Inventario FNC') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/login-fnc.css') }}">

    <style>
        .error-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--cafe) 100%);
            font-family: var(--font-family);
            padding: 24px;
        }

        .error-card {
            width: 100%;
            max-width: 460px;
            background: var(--bg-card);
            border-radius: 18px;
            box-shadow: var(--shadow-lg);
            padding: 40px 34px;
            text-align: center;
        }

        .error-logo {
            height: 76px;
            width: auto;
            object-fit: contain;
            margin-bottom: 22px;
        }

        .error-code {
            font-family: var(--font-display);
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
        }

        .error-title {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .error-message {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 26px;
            word-break: break-word;
        }

        .error-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 26px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: filter 0.2s ease;
        }

        .error-action:hover {
            filter: brightness(1.08);
            color: #ffffff;
        }

        .error-credit {
            margin-top: 22px;
            font-size: 0.72rem;
            color: var(--outline-variant);
        }
    </style>
</head>
<body class="error-page">
    <div class="error-card">
        <img src="{{ asset('imagenes/logo-fnc-tolima.png') }}" alt="Logo FNC" class="error-logo">

        <div class="error-code">403</div>
        <h1 class="error-title">Usted no tiene permisos para realizar esta acción</h1>

        <p class="error-message">
            @if (config('app.debug') && isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                Si cree que se trata de un error, comuníquese con la mesa de ayuda del Comité.
            @endif
        </p>

        <a href="{{ $backUrl }}" class="error-action">Volver</a>

        <p class="error-credit">Federación Nacional de Cafeteros de Colombia</p>
    </div>
</body>
</html>
