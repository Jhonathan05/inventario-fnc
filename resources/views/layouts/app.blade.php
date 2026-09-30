<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Inventario de Equipos') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('imagenes/federacion cafeteros logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('imagenes/federacion cafeteros logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
    @stack('styles')
    
    <meta name="current-tab-id" content="{{ request('_tab') }}">
    <script>
        (function() {
            const serverTab = document.querySelector('meta[name="current-tab-id"]').content;
            if (!serverTab) return;

            const clientTab = sessionStorage.getItem('_tab_id');

            if (!clientTab) {
                // Pestaña nueva o duplicada de otra forma donde no se copió el sessionStorage
                const newTab = Math.random().toString(36).substring(2, 10);
                sessionStorage.setItem('_tab_id', newTab);
                
                let url = new URL(window.location.href);
                url.searchParams.set('_tab', newTab);
                window.location.replace(url.toString());
            } else if (clientTab !== serverTab) {
                // Inconsistencia: La URL dice una cosa (ej. copiada de otra pestaña) y el almacenamiento dice otra
                let url = new URL(window.location.href);
                url.searchParams.set('_tab', clientTab);
                window.location.replace(url.toString());
            }

            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('form');
                const tabToUse = clientTab || serverTab;
                if (tabToUse) {
                    forms.forEach(f => {
                        if (!f.querySelector('input[name="_tab"]')) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = '_tab';
                            input.value = tabToUse;
                            f.appendChild(input);
                        }
                    });

                    // Interceptar fetch para agregar header X-Tab-Id y X-Requested-With
                    const originalFetch = window.fetch;
                    window.fetch = function() {
                        let [resource, config] = arguments;
                        if(config === undefined) { config = {}; }
                        if(config.headers === undefined) { config.headers = {}; }
                        
                        if (config.headers instanceof Headers) {
                            config.headers.append('X-Tab-Id', tabToUse);
                            config.headers.append('X-Requested-With', 'XMLHttpRequest');
                        } else {
                            config.headers['X-Tab-Id'] = tabToUse;
                            config.headers['X-Requested-With'] = 'XMLHttpRequest';
                        }
                        return originalFetch(resource, config);
                    };

                    // Interceptar navegación por enlaces (<a> tags)
                    document.addEventListener('click', function(e) {
                        const link = e.target.closest('a');
                        if (link && link.href && link.href.startsWith(window.location.origin) && !link.href.includes('javascript:')) {
                            try {
                                let url = new URL(link.href);
                                if (!url.searchParams.has('_tab')) {
                                    url.searchParams.set('_tab', tabToUse);
                                    link.href = url.toString();
                                }
                            } catch (err) {}
                        }
                    });

                    // Interceptar envío de formularios (POST) modificando el action
                    forms.forEach(f => {
                        if (f.action && f.action.startsWith(window.location.origin)) {
                            try {
                                let url = new URL(f.action);
                                if (!url.searchParams.has('_tab')) {
                                    url.searchParams.set('_tab', tabToUse);
                                    f.action = url.toString();
                                }
                            } catch (err) {}
                        }
                    });
                }
            });
        })();
    </script>
</head>
<body>

<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2">
        <img src="{{ asset('imagenes/federacion cafeteros logo.png') }}" alt="Federación Nacional de Cafeteros" width="10" height="10" class="app-sidebar-logo">
        <span>Inventario TI</span>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="{{ Route::has('inicio') ? route('inicio') : route('dashboard') }}" class="nav-link {{ request()->routeIs('inicio', 'dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Inicio
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('equipos.index') }}" class="nav-link {{ request()->routeIs('equipos.*') ? 'active' : '' }}">
                <i class="bi bi-laptop"></i> Equipos
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('tipo-recursos.index') }}" class="nav-link {{ request()->routeIs('tipo-recursos.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Tipos de Recurso
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('checklists.index') }}" class="nav-link {{ request()->routeIs('checklists.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Checklists
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('actas-firmadas.index') }}" class="nav-link {{ request()->routeIs('actas-firmadas.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-pdf"></i> Actas Firmadas
            </a>
        </li>
    </ul>
    <div class="app-sidebar-bottom">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link btn btn-link text-start w-100 app-logout-btn">
                <i class="bi bi-box-arrow-left"></i> Cerrar sesion
            </button>
        </form>
    </div>
</nav>

<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <button class="btn btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h6 class="mb-0 fw-semibold text-muted">@yield('title', 'Inventario')</h6>
        <span class="text-muted small d-none d-md-inline"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}</span>
    </div>

    <div class="content-area">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    (function () {
        function esCampoValido(el) {
            if (!el || el.disabled || el.readOnly) return false;
            if (el.dataset.noCapitalizeFirst === 'true') return false;

            if (el.tagName === 'TEXTAREA') return true;
            if (el.tagName !== 'INPUT') return false;

            const tipo = (el.getAttribute('type') || 'text').toLowerCase();
            const excluidos = ['email', 'password', 'number', 'date', 'datetime-local', 'time', 'month', 'week', 'url', 'search', 'tel', 'hidden', 'file', 'checkbox', 'radio'];
            return tipo === 'text' && !excluidos.includes(tipo);
        }

        function capitalizarPrimeraLetra(el) {
            if (!esCampoValido(el) || !el.value) return;

            const indice = el.value.search(/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u);
            if (indice < 0) return;

            const letra = el.value.charAt(indice);
            const mayuscula = letra.toLocaleUpperCase('es-CO');
            if (letra === mayuscula) return;

            el.value = el.value.slice(0, indice) + mayuscula + el.value.slice(indice + 1);
        }

        document.addEventListener('input', function (event) {
            capitalizarPrimeraLetra(event.target);
        });

        document.addEventListener('change', function (event) {
            capitalizarPrimeraLetra(event.target);
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const flashSuccess = @json(session('success'));
        const flashError = @json(session('error'));
        const flashWarning = @json(session('warning'));
        const validationErrors = @json($errors->all());

        if (flashSuccess) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: flashSuccess,
                confirmButtonText: 'OK',
                confirmButtonColor: '#9e052b',
                toast: true,
                position: 'top-end',
                timer: 2800,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        }

        if (flashWarning) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: flashWarning,
                confirmButtonText: 'OK',
                confirmButtonColor: '#9e052b',
            });
        }

        if (flashError) {
            Swal.fire({
                icon: 'error',
                title: 'Ocurrió un error',
                text: flashError,
                confirmButtonText: 'OK',
                confirmButtonColor: '#9e052b',
            });
        }

        if (Array.isArray(validationErrors) && validationErrors.length > 0) {
            const htmlErrores = '<ul style="text-align:left; margin:0; padding-left:1rem;">'
                + validationErrors.map(function (msg) { return '<li>' + msg + '</li>'; }).join('')
                + '</ul>';

            Swal.fire({
                icon: 'error',
                title: 'Revise los campos del formulario',
                html: htmlErrores,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#9e052b',
            });
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-delete-url]');
            if (!trigger) return;

            event.preventDefault();
            const url = trigger.getAttribute('data-delete-url');
            const nombre = trigger.getAttribute('data-delete-name') || 'este registro';

            const requiereConfirmacion = trigger.hasAttribute('data-delete-require-confirm');

            let swalOptions = {
                icon: 'warning',
                title: '¿Estás seguro de eliminar este registro?',
                text: 'Se eliminará ' + nombre + '. Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
            };

            if (requiereConfirmacion) {
                swalOptions.title = 'ATENCIÓN: ELIMINACIÓN PERMANENTE';
                swalOptions.html = '<p class="text-danger mb-3">Se eliminará permanentemente <strong>' + nombre + '</strong> y desaparecerá de todas las vistas.</p><p>Para proceder, escribe la palabra <strong>CONFIRMAR</strong> (en mayúsculas) en el cuadro de abajo:</p>';
                swalOptions.text = undefined; // Quitar text para usar html
                swalOptions.input = 'text';
                swalOptions.inputPlaceholder = 'Escribe CONFIRMAR';
                swalOptions.inputValidator = (value) => {
                    if (value !== 'CONFIRMAR') {
                        return 'Debes escribir la palabra CONFIRMAR exactamente en mayúsculas';
                    }
                };
            }

            Swal.fire(swalOptions).then(function (result) {
                if (!result.isConfirmed || !url) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = csrfToken;

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';

                form.appendChild(csrf);
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
