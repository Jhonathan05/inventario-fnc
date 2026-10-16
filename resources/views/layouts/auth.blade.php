<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app="inventario">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="current-tab-id" content="{{ request('_tab') }}">

    <title>@yield('title', 'Acceso Institucional') | {{ config('app.name', 'Inventario FNC') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('imagenes/federacion cafeteros logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('imagenes/federacion cafeteros logo.png') }}">

    {{-- Fuentes canónicas FNC: Outfit (headline) + Inter (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Firma corporativa login FNC (skill fnc-login, template vanilla) --}}
    <link rel="stylesheet" href="{{ asset('css/login-fnc.css') }}">

    <script src="https://unpkg.com/lucide@latest"></script>

    @stack('head')
</head>
<body class="login-mode">
    @yield('content')

    <script src="{{ asset('js/login-fnc.js') }}"></script>
    <script>
        // Contexto de pestaña (TabSessionGuard): reutiliza el `_tab` que asignó
        // el servidor y lo propaga a todos los formularios del login.
        (function () {
            const serverTab = document.querySelector('meta[name="current-tab-id"]').content;
            if (!serverTab) return;

            let tabToUse = sessionStorage.getItem('_tab_id');

            if (!tabToUse) {
                sessionStorage.setItem('_tab_id', serverTab);
                tabToUse = serverTab;
            } else if (tabToUse !== serverTab) {
                const url = new URL(window.location.href);
                url.searchParams.set('_tab', tabToUse);
                window.location.replace(url.toString());
                return;
            }

            document.querySelectorAll('form').forEach(function (form) {
                if (!form.querySelector('input[name="_tab"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_tab';
                    input.value = tabToUse;
                    form.appendChild(input);
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
