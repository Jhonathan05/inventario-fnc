<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') &mdash; Inventario ITAM</title>
    
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    {{-- Tipografía nativa del sistema (SF Pro / Segoe UI Variable) definida en
         resources/css/design-system/macos.css. Sin peticiones a Google Fonts. --}}
    <meta name="theme-color" content="#9e052b">
    <meta name="color-scheme" content="light dark">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @vite(['resources/css/app.css'])
    <style>
        /* Responsive Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        @media (max-width: 768px) {
            #sidebar {
                z-index: 1000;
            }
        }
    </style>
    @stack('styles')
    
    <meta name="current-tab-id" content="{{ request('_tab') }}">
    <script>
        (function() {
            const serverTab = document.querySelector('meta[name="current-tab-id"]').content;
            if (!serverTab) return;

            const clientTab = sessionStorage.getItem('_tab_id');

            if (!clientTab) {
                const newTab = Math.random().toString(36).substring(2, 10);
                sessionStorage.setItem('_tab_id', newTab);
                
                let url = new URL(window.location.href);
                url.searchParams.set('_tab', newTab);
                window.location.replace(url.toString());
            } else if (clientTab !== serverTab) {
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
                    
                    // URL hiding removed to prevent refresh bug
                }
            });
        })();
    </script>
</head>
<body>

{{-- Sprite local de iconos Lucide: se incluye una sola vez por documento --}}
@include('partials.lucide-sprite')

<div id="sidebar-overlay" class="sidebar-overlay d-md-none"></div>

<nav id="sidebar">
    {{-- Marca: sólo móvil. En escritorio la marca vive en la topbar. --}}
    <div class="sidebar-brand d-md-none">
        <img src="{{ asset('imagenes/logo_comite_tolima.png') }}" alt="Logo Comité Tolima">
    </div>

    <div class="nav-menu">
        <span class="sidebar-section-label">Módulos</span>
        <ul class="nav flex-column">

            @can('dashboard.ver')
            <li class="nav-item">
                <a href="{{ Route::has('inicio') ? route('inicio') : route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('inicio', 'dashboard') ? 'active' : '' }}"
                   title="Inicio">
                    <x-fnc.icon name="layout-dashboard" />
                    <span class="nav-link__label">Inicio</span>
                </a>
            </li>
            @endcan

            @can('equipos.ver')
            <li class="nav-item">
                <a href="{{ route('actas.create') }}"
                   class="nav-link {{ request()->routeIs('actas.*') ? 'active' : '' }}"
                   title="Nueva novedad de activos">
                    <x-fnc.icon name="clipboard-check" />
                    <span class="nav-link__label">Nueva novedad</span>
                </a>
            </li>
            @endcan

            @can('equipos.ver')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('equipos.*') ? 'active' : '' }}"
                   href="#menuEquipos" data-bs-toggle="collapse" role="button"
                   aria-expanded="{{ request()->routeIs('equipos.*') ? 'true' : 'false' }}"
                   aria-controls="menuEquipos"
                   data-nav-flyout="menuEquipos" data-nav-label="Activos" title="Activos">
                    <x-fnc.icon name="monitor" />
                    <span class="nav-link__label">Activos</span>
                    <span class="nav-link__chev"><x-fnc.icon name="chevron-down" :size="14" /></span>
                </a>
                <div class="collapse {{ request()->routeIs('equipos.*') ? 'show' : '' }}" id="menuEquipos">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ route('equipos.index') }}" class="nav-link {{ request()->routeIs('equipos.index') || request()->routeIs('equipos.create') || request()->routeIs('equipos.show') || request()->routeIs('equipos.edit') ? 'active' : '' }}">
                                <x-fnc.icon name="laptop" :size="16" />
                                <span class="nav-link__label">Inventario</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('equipos.complementos.global') }}" class="nav-link {{ request()->routeIs('equipos.complementos.global') ? 'active' : '' }}">
                                <x-fnc.icon name="package" :size="16" />
                                <span class="nav-link__label">Complementos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('prestamos.index') }}" class="nav-link {{ request()->routeIs('prestamos.*') ? 'active' : '' }}">
                                <x-fnc.icon name="arrow-right-left" :size="16" />
                                <span class="nav-link__label">Préstamos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            @can('usuarios.ver')
            <li class="nav-item">
                <a href="{{ route('funcionarios.index') }}" class="nav-link {{ request()->routeIs('funcionarios.*') ? 'active' : '' }}" title="Funcionarios">
                    <x-fnc.icon name="users" />
                    <span class="nav-link__label">Funcionarios</span>
                </a>
            </li>
            @endcan

            @can('mesaayuda.ver')
            <li class="nav-item">
                <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}" title="HelpDesk">
                    <x-fnc.icon name="headset" />
                    <span class="nav-link__label">HelpDesk</span>
                </a>
            </li>
            @endcan

            @can('historial.ver')
            <li class="nav-item">
                <a href="{{ route('historial-tecnico.index') }}" class="nav-link {{ request()->routeIs('historial-tecnico.*') ? 'active' : '' }}" title="Reparaciones">
                    <x-fnc.icon name="wrench" />
                    <span class="nav-link__label">Reparaciones</span>
                </a>
            </li>
            @endcan

            @canany(['configuracion.editar', 'campos_personalizados.ver'])
            <li class="nav-item">
                @php
                    $isConfigActive = request()->routeIs('tipo-recursos.*', 'campos-personalizados.*');
                @endphp
                <a class="nav-link {{ $isConfigActive ? 'active' : '' }}"
                   href="#menuConfiguracion" data-bs-toggle="collapse" role="button"
                   aria-expanded="{{ $isConfigActive ? 'true' : 'false' }}"
                   aria-controls="menuConfiguracion"
                   data-nav-flyout="menuConfiguracion" data-nav-label="Configuración" title="Configuración">
                    <x-fnc.icon name="settings" />
                    <span class="nav-link__label">Configuración</span>
                    <span class="nav-link__chev"><x-fnc.icon name="chevron-down" :size="14" /></span>
                </a>
                <div class="collapse {{ $isConfigActive ? 'show' : '' }}" id="menuConfiguracion">
                    <ul class="nav flex-column">
                        @can('configuracion.editar')
                        <li class="nav-item">
                            <a href="{{ route('tipo-recursos.index') }}" class="nav-link {{ request()->routeIs('tipo-recursos.*') ? 'active' : '' }}">
                                <x-fnc.icon name="tags" :size="16" />
                                <span class="nav-link__label">Categorías</span>
                            </a>
                        </li>
                        @endcan

                        @can('campos_personalizados.ver')
                        <li class="nav-item">
                            <a href="{{ route('campos-personalizados.index') }}" class="nav-link {{ request()->routeIs('campos-personalizados.*') ? 'active' : '' }}">
                                <x-fnc.icon name="list-checks" :size="16" />
                                <span class="nav-link__label">Campos Personalizados</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany

            @can('licencias.ver')
            <li class="nav-item">
                <a href="{{ route('licencias.index') }}" class="nav-link {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}" title="Licencias">
                    <x-fnc.icon name="key-round" />
                    <span class="nav-link__label">Licencias</span>
                </a>
            </li>
            @endcan

            @can('dashboard.ver')
            <li class="nav-item">
                <a href="{{ route('reportes.index') }}" class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Reportes">
                    <x-fnc.icon name="chart-column" />
                    <span class="nav-link__label">Reportes</span>
                </a>
            </li>
            @endcan

            @can('configuracion.editar')
            <li class="nav-item">
                <a href="{{ route('plantillas-pdf.index') }}" class="nav-link {{ request()->routeIs('plantillas-pdf.*') ? 'active' : '' }}" title="Actas">
                    <x-fnc.icon name="file-check" />
                    <span class="nav-link__label">Actas</span>
                </a>
            </li>
            @endcan

            @can('equipos.ver')
            <li class="nav-item">
                <a href="{{ route('actas-firmadas.index') }}" class="nav-link {{ request()->routeIs('actas-firmadas.*') ? 'active' : '' }}" title="Actas Firmadas">
                    <x-fnc.icon name="file-badge" />
                    <span class="nav-link__label">Actas Firmadas</span>
                </a>
            </li>
            @endcan

            @can('equipos.importar')
            <li class="nav-item">
                <a href="{{ route('equipos.importar.form') }}" class="nav-link {{ request()->routeIs('equipos.importar*') ? 'active' : '' }}" title="Importar">
                    <x-fnc.icon name="upload" />
                    <span class="nav-link__label">Importar</span>
                </a>
            </li>
            @endcan

            @can('roles.ver')
            <li class="nav-item nav-section">
                <span class="sidebar-section-label">Seguridad</span>
            </li>
            <li class="nav-item">
                <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" title="Usuarios Sistema">
                    <x-fnc.icon name="user-cog" />
                    <span class="nav-link__label">Usuarios Sistema</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" title="Roles y Permisos">
                    <x-fnc.icon name="shield-check" />
                    <span class="nav-link__label">Roles y Permisos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('auditoria.index') }}" class="nav-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}" title="Auditoría">
                    <x-fnc.icon name="scroll-text" />
                    <span class="nav-link__label">Auditoría</span>
                </a>
            </li>
            @endcan

            @can('configuracion.editar')
            <li class="nav-item">
                <a href="#" class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}" title="Backups">
                    <x-fnc.icon name="database" />
                    <span class="nav-link__label">Backups</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>

    <div class="sidebar-footer">
        {{-- Accesibilidad: círculo con tarjeta al pasar el cursor (tamaño de
             letra y tema claro/oscuro, que se muda aquí desde la topbar) --}}
        <div class="a11y-menu">
            <button class="a11y-trigger" id="a11yTriggerBtn" type="button" aria-label="Accesibilidad" title="Accesibilidad">
                <x-fnc.icon name="accessibility" :size="18" />
            </button>
            <div class="a11y-pop" role="menu" aria-label="Opciones de accesibilidad">
                <button class="a11y-pop__item" id="fontDownBtn" type="button" role="menuitem">
                    <span class="a11y-pop__glyph" aria-hidden="true">A−</span>
                    <span>Disminuir letra</span>
                </button>
                <button class="a11y-pop__item" id="fontUpBtn" type="button" role="menuitem">
                    <span class="a11y-pop__glyph" aria-hidden="true">A+</span>
                    <span>Aumentar letra</span>
                </button>
                <button class="a11y-pop__item" id="themeFooterBtn" type="button" role="menuitem">
                    <span class="a11y-pop__glyph" aria-hidden="true"><x-fnc.icon name="moon" :size="14" /></span>
                    <span>Tema claro / oscuro</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Flyout en árbol del rail: lo rellena fnc-shell (sólo escritorio) --}}
    <div class="nav-flyout" id="navFlyout" aria-hidden="true"></div>
</nav>

<div class="main-content">
    <header class="topbar">
        <div class="topbar__left">
            <button class="topbar-icon-btn d-md-none" id="sidebarToggleBtn" type="button" aria-label="Abrir menú de navegación" title="Menú">
                <x-fnc.icon name="menu" :size="18" />
            </button>

            {{-- Marca: vive en la topbar en todos los tamaños --}}
            <a href="{{ Route::has('inicio') ? route('inicio') : route('dashboard') }}" class="topbar-brand" aria-label="Ir al Inicio">
                <img src="{{ asset('imagenes/itms.png') }}" alt="ITAM">
            </a>

            <span class="brand-divider d-none d-lg-block" aria-hidden="true"></span>

            <div class="topbar-title text-truncate d-none d-lg-flex flex-column">
                <h5 class="mb-0 text-truncate"><strong>Inventario</strong> ITAM</h5>
            </div>
        </div>

        {{-- Centro: buscador global y, en el tablero, el selector de período --}}
        <div class="topbar__center">
            @hasSection('topbar-center')
                @yield('topbar-center')
            @else
                <button class="topbar-search" type="button" id="fncPaletteTrigger"
                        aria-haspopup="dialog" aria-controls="fncPalette" aria-expanded="false"
                        title="Buscar módulo o acción (Ctrl + K)">
                    <x-fnc.icon name="search" :size="16" />
                    <span class="topbar-search__text d-none d-sm-inline">Buscar módulo o acción…</span>
                    <span class="fnc-kbd d-none d-lg-inline-flex">Ctrl</span>
                    <span class="fnc-kbd d-none d-lg-inline-flex">K</span>
                </button>
            @endif
        </div>

        <div class="topbar__right">
            <!-- Accesos rápidos en móvil (Complementos, Préstamos y tema) -->
            <div class="d-md-none d-flex align-items-center gap-2">
                @can('equipos.ver')
                <a href="{{ route('equipos.complementos.global') }}" class="topbar-icon-btn" aria-label="Complementos" title="Complementos">
                    <x-fnc.icon name="package" :size="18" />
                </a>
                <a href="{{ route('prestamos.index') }}" class="topbar-icon-btn" aria-label="Préstamos" title="Préstamos">
                    <x-fnc.icon name="arrow-right-left" :size="18" />
                </a>
                @endcan
                <button class="topbar-icon-btn" id="themeMobileBtn" type="button" aria-label="Cambiar tema" title="Cambiar tema (claro / oscuro)">
                    <span class="d-inline-flex" id="themeMobileIcon"><x-fnc.icon name="moon" :size="18" /></span>
                </button>
            </div>

            {{-- Sesión de usuario: píldora plana (avatar + nombre + cheurón) con
                 menú por hover, como los círculos planos del header de la referencia.
                 El tema claro/oscuro se mudó a la tarjeta de accesibilidad del rail. --}}
            <div class="user-menu">
                <button class="user-profile user-menu-trigger" type="button" aria-haspopup="true" aria-label="Menú de usuario">
                    <span class="user-profile__text d-none d-md-flex flex-column text-end">
                        <span class="user-profile__name">{{ auth()->user()->name ?? 'ADMIN' }}</span>
                        <span class="user-profile__role">{{ auth()->user()?->roles->first()->name ?? 'Usuario' }}</span>
                    </span>
                    <span class="user-avatar">{{ substr(auth()->user()->name ?? 'A', 0, 1) }}</span>
                </button>
                <div class="user-menu-pop" role="menu">
                    <a class="user-menu-item" role="menuitem" href="{{ route('usuarios.edit', auth()->id()) }}">
                        <x-fnc.icon name="user-round" :size="15" class="me-2" /> Mi perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0">
                        @csrf
                        <button class="user-menu-item text-danger" type="submit" role="menuitem">
                            <x-fnc.icon name="log-out" :size="15" class="me-2" /> Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="content-area">
        @yield('content')
    </div>
</div>

<!-- ============================================== -->
<!-- BARRA DE NAVEGACIÓN INFERIOR (SÓLO MÓVIL)       -->
<!-- ============================================== -->
<nav class="bottom-nav d-md-none">
    <div class="bottom-nav-inner">
        @can('equipos.ver')
        <a href="{{ route('equipos.index') }}" class="bottom-nav-item {{ request()->routeIs('equipos.*', 'prestamos.*') ? 'active' : '' }}">
            <x-fnc.icon name="monitor" :size="22" />
            <span>Activos</span>
        </a>
        @endcan
        
        @can('usuarios.ver')
        <a href="{{ route('funcionarios.index') }}" class="bottom-nav-item {{ request()->routeIs('funcionarios.*') ? 'active' : '' }}">
            <x-fnc.icon name="users" :size="22" />
            <span>Funcionarios</span>
        </a>
        @endcan
        

        
        @can('licencias.ver')
        <a href="{{ route('licencias.index') }}" class="bottom-nav-item {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}">
            <x-fnc.icon name="key-round" :size="22" />
            <span>Licencias</span>
        </a>
        @endcan
        
        <!-- Botón MÁS (Abre el overlay) -->
        <a href="#" class="bottom-nav-item" id="btnMobileMore">
            <x-fnc.icon name="layout-grid" :size="22" />
            <span>Más</span>
        </a>
    </div>
</nav>

{{-- Volver arriba: aparece al hacer scroll y sube hasta el tope --}}
<button class="fnc-scrolltop" id="scrollTopFab" type="button" aria-label="Volver arriba" title="Volver arriba">
    <x-fnc.icon name="arrow-up" :size="18" />
</button>

<!-- BOTÓN FLOTANTE ESCANEAR (SOLO MÓVIL) -->
@can('equipos.ver')
<button class="scan-fab d-md-none" id="scanFab" data-scan-url="{{ route('equipos.buscar-placa') }}" aria-label="Escanear código de barras" title="Escanear placa">
    <x-fnc.icon name="scan-barcode" :size="22" />
</button>
@endcan
<!-- SCANNER FULLSCREEN (SOLO MÓVIL) -->
<div class="scanner-overlay" id="scannerOverlay">
    <div class="scanner-header">
        <h5><x-fnc.icon name="scan-barcode" class="me-2" />Escanear Placa</h5>
        <button class="scanner-close-btn" id="scannerClose" aria-label="Cerrar escáner">&times;</button>
    </div>
    <div class="scanner-viewfinder">
        <video id="scannerVideo" muted playsinline></video>
        <div class="scanner-frame"></div>
        <!-- Resultado -->
        <div class="scanner-result" id="scannerResult">
            <div class="scanner-result-card">
                <div class="scanner-result-icon" id="scannerResultIcon">
                    <x-fnc.icon name="check" :size="26" />
                </div>
                <div class="scanner-result-title" id="scannerResultTitle">Activo encontrado</div>
                <div class="scanner-result-placa" id="scannerResultPlaca"></div>
                <div class="scanner-result-actions">
                    <button class="btn btn-primary" id="scannerResultGo">
                        <x-fnc.icon name="eye" class="me-1" /> Ver detalle del activo
                    </button>
                    <button class="btn btn-outline-secondary" id="scannerRetry">
                        <x-fnc.icon name="scan-barcode" class="me-1" /> Escanear nuevamente
                    </button>
                    <button class="btn btn-outline-dark" id="scannerResultClose">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="scanner-footer">
        <div class="scanner-hint">Apunta al código de barras de la placa del activo</div>
        <div class="scanner-status" id="scannerStatus"></div>
    </div>
</div>

<!-- ============================================== -->
<!-- OVERLAY "MÁS" (MENÚ COMPLETO MÓVIL)            -->
<!-- ============================================== -->
<div class="mobile-more-overlay d-md-none" id="mobileMoreOverlay">
    <div class="mobile-more-header">
        <h5 class="mobile-more-title">Menú de Navegación</h5>
        <button class="mobile-more-close" id="btnMobileMoreClose">&times;</button>
    </div>
    
    <div class="mobile-more-body">
        
        <!-- Sección Módulos -->
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Módulos Principales</div>
            
            @can('mesaayuda.ver')
            <a href="{{ route('tickets.index') }}" class="mobile-more-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <x-fnc.icon name="headset" /> HelpDesk
            </a>
            @endcan
            
            @can('historial.ver')
            <a href="{{ route('historial-tecnico.index') }}" class="mobile-more-link {{ request()->routeIs('historial-tecnico.*') ? 'active' : '' }}">
                <x-fnc.icon name="wrench" /> Reparaciones
            </a>
            @endcan
            
            @can('licencias.ver')
            <a href="{{ route('licencias.index') }}" class="mobile-more-link {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}">
                <x-fnc.icon name="key-round" /> Licencias
            </a>
            @endcan
            
            @can('dashboard.ver')
            <a href="{{ route('reportes.index') }}" class="mobile-more-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                <x-fnc.icon name="chart-column" /> Reportes
            </a>
            @endcan
            
            @can('equipos.ver')
            <a href="{{ route('actas-firmadas.index') }}" class="mobile-more-link {{ request()->routeIs('actas-firmadas.*') ? 'active' : '' }}">
                <x-fnc.icon name="file-badge" /> Actas Firmadas
            </a>
            @endcan
            
            @can('equipos.importar')
            <a href="{{ route('equipos.importar.form') }}" class="mobile-more-link {{ request()->routeIs('equipos.importar*') ? 'active' : '' }}">
                <x-fnc.icon name="upload" /> Importar Datos
            </a>
            @endcan
        </div>
        
        <!-- Sección Configuración -->
        @canany(['configuracion.editar', 'campos_personalizados.ver'])
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Configuración</div>
            
            @can('configuracion.editar')
            <a href="{{ route('tipo-recursos.index') }}" class="mobile-more-link {{ request()->routeIs('tipo-recursos.*') ? 'active' : '' }}">
                <x-fnc.icon name="tags" /> Categorías
            </a>
            @endcan
            
            @can('campos_personalizados.ver')
            <a href="{{ route('campos-personalizados.index') }}" class="mobile-more-link {{ request()->routeIs('campos-personalizados.*') ? 'active' : '' }}">
                <x-fnc.icon name="list-checks" /> Campos Personalizados
            </a>
            @endcan
            
            @can('configuracion.editar')
            <a href="{{ route('plantillas-pdf.index') }}" class="mobile-more-link {{ request()->routeIs('plantillas-pdf.*') ? 'active' : '' }}">
                <x-fnc.icon name="file-check" /> Plantillas de Actas
            </a>
            @endcan
        </div>
        @endcanany

        <!-- Sección Seguridad -->
        @can('roles.ver')
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Seguridad</div>
            
            <a href="{{ route('usuarios.index') }}" class="mobile-more-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <x-fnc.icon name="user-cog" /> Usuarios Sistema
            </a>
            <a href="{{ route('roles.index') }}" class="mobile-more-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <x-fnc.icon name="shield-check" /> Roles y Permisos
            </a>
            <a href="{{ route('auditoria.index') }}" class="mobile-more-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}">
                <x-fnc.icon name="scroll-text" /> Auditoría
            </a>
            @can('configuracion.editar')
            <a href="#" class="mobile-more-link {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                <x-fnc.icon name="database" /> Backups
            </a>
            @endcan
        </div>
        @endcan
        
        <!-- Sección Cuenta -->
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Mi Cuenta</div>
            
            <div class="mobile-more-link mb-2" style="background:transparent; padding:0 16px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar" style="width:40px; height:40px; border-radius:50%; background:var(--primary-light); color:var(--primary-color); display:flex; align-items:center; justify-content:center; font-weight:700;">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <div style="font-weight:700; color:var(--text-dark); font-size:0.95rem;">{{ auth()->user()->name ?? 'ADMIN' }}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">{{ auth()->user()?->roles->first()->name ?? 'Usuario' }}</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mobile-logout-form">
                @csrf
                <button type="submit" class="mobile-logout-btn">
                    <x-fnc.icon name="log-out" /> Cerrar Sesión
                </button>
            </form>
        </div>

    </div>
</div>

@php
    // Destinos de la paleta de comandos: sólo rutas existentes y permitidas.
    $fncRutaInicio = \Illuminate\Support\Facades\Route::has('inicio') ? 'inicio' : 'dashboard';
    $fncUsuario = auth()->user();

    $fncNavegacion = [
        ['group' => 'Principal', 'label' => 'Nueva novedad de activos', 'icon' => 'clipboard-check', 'route' => 'actas.create', 'permission' => 'equipos.ver', 'keywords' => ['acta', 'novedad', 'asignar', 'inventario', 'fisico', 'prestamo', 'reparacion', 'retiro', 'traslado', 'cambio', 'responsable']],
        ['group' => 'Principal', 'label' => 'Panel de control', 'icon' => 'layout-dashboard', 'route' => $fncRutaInicio, 'permission' => 'dashboard.ver', 'keywords' => ['inicio', 'dashboard', 'kpi']],
        ['group' => 'Activos', 'label' => 'Inventario de activos', 'icon' => 'monitor', 'route' => 'equipos.index', 'permission' => 'equipos.ver', 'keywords' => ['equipos', 'activos', 'inventario']],
        ['group' => 'Activos', 'label' => 'Complementos', 'icon' => 'package', 'route' => 'equipos.complementos.global', 'permission' => 'equipos.ver'],
        ['group' => 'Activos', 'label' => 'Préstamos', 'icon' => 'arrow-right-left', 'route' => 'prestamos.index', 'permission' => 'equipos.ver'],
        ['group' => 'Activos', 'label' => 'Funcionarios', 'icon' => 'users', 'route' => 'funcionarios.index', 'permission' => 'usuarios.ver'],
        ['group' => 'Activos', 'label' => 'Importar activos', 'icon' => 'upload', 'route' => 'equipos.importar.form', 'permission' => 'equipos.importar'],
        ['group' => 'Operación', 'label' => 'HelpDesk', 'icon' => 'headset', 'route' => 'tickets.index', 'permission' => 'mesaayuda.ver', 'keywords' => ['tickets', 'soporte']],
        ['group' => 'Operación', 'label' => 'Reparaciones', 'icon' => 'wrench', 'route' => 'historial-tecnico.index', 'permission' => 'historial.ver'],
        ['group' => 'Operación', 'label' => 'Licencias', 'icon' => 'key-round', 'route' => 'licencias.index', 'permission' => 'licencias.ver'],
        ['group' => 'Operación', 'label' => 'Reportes', 'icon' => 'chart-column', 'route' => 'reportes.index', 'permission' => 'dashboard.ver'],
        ['group' => 'Configuración', 'label' => 'Categorías de activos', 'icon' => 'tags', 'route' => 'tipo-recursos.index', 'permission' => 'configuracion.editar'],
        ['group' => 'Configuración', 'label' => 'Campos personalizados', 'icon' => 'list-checks', 'route' => 'campos-personalizados.index', 'permission' => 'campos_personalizados.ver'],
        ['group' => 'Configuración', 'label' => 'Plantillas de actas', 'icon' => 'file-check', 'route' => 'plantillas-pdf.index', 'permission' => 'configuracion.editar'],
        ['group' => 'Seguridad', 'label' => 'Usuarios del sistema', 'icon' => 'user-cog', 'route' => 'usuarios.index', 'permission' => 'roles.ver'],
        ['group' => 'Seguridad', 'label' => 'Roles y permisos', 'icon' => 'shield-check', 'route' => 'roles.index', 'permission' => 'roles.ver'],
        ['group' => 'Seguridad', 'label' => 'Auditoría', 'icon' => 'scroll-text', 'route' => 'auditoria.index', 'permission' => 'roles.ver'],
    ];

    $fncDestinos = collect($fncNavegacion)
        ->filter(fn (array $item): bool => \Illuminate\Support\Facades\Route::has($item['route'])
            && $fncUsuario !== null
            && $fncUsuario->can($item['permission']))
        ->map(fn (array $item): array => [
            'group' => $item['group'],
            'label' => $item['label'],
            'icon' => $item['icon'],
            'keywords' => $item['keywords'] ?? [],
            'url' => route($item['route']),
        ])
        ->values()
        ->all();

    if ($fncUsuario !== null) {
        $fncDestinos[] = [
            'group' => 'Cuenta',
            'label' => 'Mi perfil',
            'icon' => 'user-round',
            'keywords' => ['perfil', 'cuenta'],
            'url' => route('usuarios.edit', $fncUsuario->id),
        ];
    }
@endphp

<!-- ============================================== -->
<!-- PALETA DE COMANDOS (Ctrl / Cmd + K)            -->
<!-- ============================================== -->
<div class="fnc-palette" id="fncPalette" role="dialog" aria-modal="true" aria-labelledby="fncPaletteLabel" hidden>
    <div class="fnc-palette__dialog">
        <div class="fnc-palette__field">
            <x-fnc.icon name="search" :size="18" />
            <label class="fnc-sr" for="fncPaletteInput" id="fncPaletteLabel">Buscar módulo o acción</label>
            <input id="fncPaletteInput" type="text" autocomplete="off" spellcheck="false"
                   placeholder="Buscar módulo o acción…" aria-controls="fncPaletteList">
            <span class="fnc-kbd">Esc</span>
        </div>

        <ul class="fnc-palette__list" id="fncPaletteList" role="listbox" aria-label="Destinos disponibles"></ul>

        <div class="fnc-palette__foot">
            <span><span class="fnc-kbd">↑</span> <span class="fnc-kbd">↓</span> navegar</span>
            <span><span class="fnc-kbd">Enter</span> abrir</span>
            <span class="fnc-spacer"></span>
            <span>{{ count($fncDestinos) }} destinos</span>
        </div>
    </div>
</div>

<script>
    window.fncPaletteItems = @json($fncDestinos);
</script>
<script src="{{ asset('js/fnc-shell.js') }}?v={{ time() }}"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/app-core.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/fnc-combobox.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica del menú móvil "Más"
        const btnMobileMore = document.getElementById('btnMobileMore');
        const btnMobileMoreClose = document.getElementById('btnMobileMoreClose');
        const mobileMoreOverlay = document.getElementById('mobileMoreOverlay');

        if (btnMobileMore && btnMobileMoreClose && mobileMoreOverlay) {
            btnMobileMore.addEventListener('click', function(e) {
                e.preventDefault();
                mobileMoreOverlay.classList.add('show');
                document.body.style.overflow = 'hidden'; // Evitar scroll de fondo
            });

            btnMobileMoreClose.addEventListener('click', function() {
                mobileMoreOverlay.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.initAlerts(
            @json(session('success')),
            @json(session('error')),
            @json(session('warning')),
            @json($errors->all())
        );

        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

        function toggleSidebar() {
            if (sidebar) sidebar.classList.toggle('show');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
        }

        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        /* ---- Rail de iconos (sólo iconos, sin modo expandido) + flyout en árbol ---- */
        const navFlyout = document.getElementById('navFlyout');
        const navRoot = document.documentElement;

        function esRail() {
            return window.matchMedia('(min-width: 769px)').matches;
        }

        let flyoutTimer = null;
        let flyoutAnchor = null;

        function hideFlyout() {
            if (flyoutTimer) { window.clearTimeout(flyoutTimer); flyoutTimer = null; }
            if (navFlyout) {
                navFlyout.classList.remove('is-open');
                navFlyout.setAttribute('aria-hidden', 'true');
                navFlyout.innerHTML = '';
            }
            flyoutAnchor = null;
        }

        function scheduleHide() {
            if (flyoutTimer) { window.clearTimeout(flyoutTimer); }
            flyoutTimer = window.setTimeout(hideFlyout, 150);
        }

        function showFlyout(link) {
            if (!navFlyout || !sidebar) { return; }

            const origen = document.getElementById(link.getAttribute('data-nav-flyout') || '');
            const lista = origen ? origen.querySelector('.nav') : null;
            if (!lista) { return; }

            if (flyoutTimer) { window.clearTimeout(flyoutTimer); flyoutTimer = null; }

            navFlyout.innerHTML = '';
            const titulo = document.createElement('span');
            titulo.className = 'nav-flyout__title';
            titulo.textContent = link.getAttribute('data-nav-label') || '';
            navFlyout.appendChild(titulo);
            navFlyout.appendChild(lista.cloneNode(true));

            const base = sidebar.getBoundingClientRect();
            const ancla = link.getBoundingClientRect();
            navFlyout.style.top = Math.max(0, ancla.top - base.top - 8) + 'px';
            navFlyout.classList.add('is-open');
            navFlyout.setAttribute('aria-hidden', 'false');

            // Si el flyout se sale por abajo, se sube para que quepa.
            const caja = navFlyout.getBoundingClientRect();
            if (caja.bottom > window.innerHeight - 10) {
                navFlyout.style.top = Math.max(0, window.innerHeight - 10 - caja.height - base.top) + 'px';
            }
            flyoutAnchor = link;
        }

        // -------------------------------------------------------------
        // Topbar que se esconde al bajar + botón para volver al tope.
        // La paleta de comandos acompaña al header: si estaba abierta, se cierra
        // con el mismo flujo de Escape que usa fnc-shell.js.
        // -------------------------------------------------------------
        const scrollTopFab = document.getElementById('scrollTopFab');
        let ultimoScrollY = window.scrollY;
        let topbarOculta = false;

        function sincronizarTopbar() {
            const y = window.scrollY;
            const delta = y - ultimoScrollY;
            ultimoScrollY = y;

            if (!topbarOculta && delta > 2 && y > 0) {
                topbarOculta = true;
                document.documentElement.classList.add('is-topbar-hidden');
                document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            } else if (topbarOculta && (delta < -2 || y <= 0)) {
                topbarOculta = false;
                document.documentElement.classList.remove('is-topbar-hidden');
            }

            if (scrollTopFab) {
                scrollTopFab.classList.toggle('is-visible', y > 320);
            }
        }

        window.addEventListener('scroll', sincronizarTopbar, { passive: true });
        sincronizarTopbar();

        if (scrollTopFab) {
            scrollTopFab.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        if (sidebar && navFlyout) {
            sidebar.addEventListener('mouseover', function (event) {
                const link = event.target.closest('[data-nav-flyout]');
                if (!link) { return; }
                if (!esRail()) { hideFlyout(); return; }
                if (link === flyoutAnchor) {
                    if (flyoutTimer) { window.clearTimeout(flyoutTimer); flyoutTimer = null; }
                    return;
                }
                showFlyout(link);
            });

            sidebar.addEventListener('mouseleave', scheduleHide);
            navFlyout.addEventListener('mouseenter', function () {
                if (flyoutTimer) { window.clearTimeout(flyoutTimer); flyoutTimer = null; }
            });
            navFlyout.addEventListener('mouseleave', scheduleHide);

            // En modo rail el clic en un padre abre el flyout (no el collapse inline)
            sidebar.addEventListener('click', function (event) {
                const link = event.target.closest('[data-nav-flyout]');
                if (!link || !esRail()) { return; }
                event.preventDefault();
                event.stopPropagation();
                showFlyout(link);
            }, true);

            document.addEventListener('click', function (event) {
                if (!esRail() || !navFlyout.classList.contains('is-open')) { return; }
                if (!event.target.closest('#sidebar')) { hideFlyout(); }
            });

            window.addEventListener('resize', function () {
                if (!esRail()) { hideFlyout(); }
            });
        }

        // Auto-cerrar sidebar en móvil al seleccionar opción
        if (sidebar && window.matchMedia('(max-width: 768px)').matches) {
            const navLinks = sidebar.querySelectorAll('.nav-link:not([data-bs-toggle="collapse"])');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    sidebar.classList.remove('show');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                });
            });
        }



        // Gestor de Tema Oscuro/Claro (persistencia vía localStorage).
        // El interruptor vive en la tarjeta de accesibilidad del rail.
        const themeFooterBtn = document.getElementById('themeFooterBtn');
        const savedTheme = localStorage.getItem('fnc_theme') || 'light';

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            syncFooterThemeBtn(theme);
        }

        // Icono Lucide del sprite local (sin dependencias externas).
        function iconoTema(theme, size) {
            const nombre = theme === 'dark' ? 'sun' : 'moon';

            return '<svg class="fnc-icon" width="' + size + '" height="' + size + '" viewBox="0 0 24 24"'
                + ' fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"'
                + ' stroke-linejoin="round" aria-hidden="true" focusable="false">'
                + '<use href="#fnc-i-' + nombre + '"></use></svg>';
        }

        applyTheme(savedTheme);

        function alternarTema() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('fnc_theme', newTheme);
            applyTheme(newTheme);
        }

        if (themeFooterBtn) {
            themeFooterBtn.addEventListener('click', alternarTema);
        }

        // En móvil el interruptor de tema vive en la topbar (no hay rail).
        const themeMobileBtn = document.getElementById('themeMobileBtn');
        if (themeMobileBtn) {
            themeMobileBtn.addEventListener('click', alternarTema);
        }

        // Accesibilidad estilo sip-fnc v1.6.0: tamaño de letra 80-120%.
        const fontDownBtn = document.getElementById('fontDownBtn');
        const fontUpBtn = document.getElementById('fontUpBtn');

        function getFontSize() {
            return parseInt(localStorage.getItem('inv_font') || '100', 10);
        }

        function syncFont() {
            const s = getFontSize();
            if (fontDownBtn) {
                fontDownBtn.disabled = s <= 80;
                fontDownBtn.classList.toggle('is-default', s === 100);
                fontDownBtn.title = 'Disminuir tamaño de letra (' + s + '%)';
            }
            if (fontUpBtn) {
                fontUpBtn.disabled = s >= 120;
                fontUpBtn.classList.toggle('is-default', s === 100);
                fontUpBtn.title = 'Aumentar tamaño de letra (' + s + '%)';
            }
        }

        function setFont(s) {
            s = Math.min(120, Math.max(80, s));
            document.documentElement.style.fontSize = (s / 100 * 16) + 'px';
            try { localStorage.setItem('inv_font', String(s)); } catch (e) {}
            syncFont();
        }

        function syncFooterThemeBtn(theme) {
            if (themeFooterBtn) {
                const glyph = themeFooterBtn.querySelector('.a11y-pop__glyph');
                if (glyph) { glyph.innerHTML = iconoTema(theme, 14); }
                themeFooterBtn.title = theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
                themeFooterBtn.setAttribute('aria-label', themeFooterBtn.title);
            }
            const iconoMovil = document.getElementById('themeMobileIcon');
            if (iconoMovil) { iconoMovil.innerHTML = iconoTema(theme, 18); }
        }

        const savedFont = getFontSize();
        if (savedFont !== 100) {
            document.documentElement.style.fontSize = (savedFont / 100 * 16) + 'px';
        }
        syncFont();

        if (fontDownBtn) fontDownBtn.addEventListener('click', () => setFont(getFontSize() - 10));
        if (fontUpBtn) fontUpBtn.addEventListener('click', () => setFont(getFontSize() + 10));
    });
</script>
@stack('scripts')
    <!-- Validaciones en tiempo real -->
    <script src="{{ asset('js/realtime-validations.js') }}?v={{ time() }}"></script>
    <!-- Lector de código de barras (solo móvil) -->
    <script src="{{ asset('js/barcode-scanner.js') }}?v={{ time() }}"></script>

    @if (session('mock_auth'))
        {{-- Indicador MODO MOCK (skill fnc-mock-auth): visible sólo en sesión mock --}}
        <style>
            .mock-mode-indicator {
                position: fixed;
                /* Debajo de la topbar (70px + 10px de margen) para no tapar el
                   menú de usuario en sesiones mock. */
                top: 88px;
                right: 12px;
                z-index: 1080;
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 999px;
                background: rgba(158, 5, 43, 0.92);
                color: #fff;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.6px;
                text-transform: uppercase;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
                cursor: help;
                opacity: 0.85;
                transition: opacity 0.2s ease;
            }

            .mock-mode-indicator:hover {
                opacity: 1;
            }

            .mock-mode-indicator::before {
                content: '';
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #fff;
            }
        </style>
        <div class="mock-mode-indicator" title="Sesión mock de desarrollo: usuarios sintéticos, sin Keycloak.">
            Modo Mock
        </div>
    @endif
</body>
</html>
