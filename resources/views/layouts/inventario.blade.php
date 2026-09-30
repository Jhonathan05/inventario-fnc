<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventario TIC') &mdash; Federación Nacional de Cafeteros</title>
    
    <link rel="icon" type="image/png" href="{{ asset('imagenes/federacion cafeteros logo.png') }}">
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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

<div id="sidebar-overlay" class="sidebar-overlay d-md-none"></div>

<nav id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('imagenes/logo_comite_tolima.png') }}" alt="Logo Comité Tolima">
    </div>
    
    <div class="nav-menu">
        <ul class="nav flex-column">
            
            @can('dashboard.ver')
            <li class="nav-item">
                <a href="{{ Route::has('inicio') ? route('inicio') : route('dashboard') }}" class="nav-link {{ request()->routeIs('inicio', 'dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Inicio
                </a>
            </li>
            @endcan
            
            @can('equipos.ver')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('equipos.*') ? 'active' : '' }}" href="#menuEquipos" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('equipos.*') ? 'true' : 'false' }}" aria-controls="menuEquipos">
                    <i class="bi bi-display"></i> <span>Activos</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem; transition: transform 0.3s;"></i>
                </a>
                <div class="collapse {{ request()->routeIs('equipos.*') ? 'show' : '' }}" id="menuEquipos" style="visibility: visible;">
                    <ul class="nav flex-column" style="padding-left: 1rem; margin-top: 4px; margin-bottom: 4px;">
                        <li class="nav-item">
                            <a href="{{ route('equipos.index') }}" class="nav-link {{ request()->routeIs('equipos.index') || request()->routeIs('equipos.create') || request()->routeIs('equipos.show') || request()->routeIs('equipos.edit') ? 'active' : '' }}" style="padding-top: 6px; padding-bottom: 6px; margin-bottom: 2px;">
                                <i class="bi bi-pc-display"></i> <span>Inventario</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('equipos.complementos.global') }}" class="nav-link {{ request()->routeIs('equipos.complementos.global') ? 'active' : '' }}" style="padding-top: 6px; padding-bottom: 6px; margin-bottom: 2px;">
                                <i class="bi bi-box-seam"></i> <span>Complementos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('prestamos.index') }}" class="nav-link {{ request()->routeIs('prestamos.*') ? 'active' : '' }}" style="padding-top: 6px; padding-bottom: 6px; margin-bottom: 2px;">
                                <i class="bi bi-calendar2-range"></i> <span>Préstamos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan
            
            @can('usuarios.ver')
            <li class="nav-item">
                <a href="{{ route('funcionarios.index') }}" class="nav-link {{ request()->routeIs('funcionarios.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Funcionarios
                </a>
            </li>
            @endcan
            
            @can('mesaayuda.ver')
            <li class="nav-item">
                <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    <i class="bi bi-headset"></i> HelpDesk
                </a>
            </li>
            @endcan
            
            @can('historial.ver')
            <li class="nav-item">
                <a href="{{ route('historial-tecnico.index') }}" class="nav-link {{ request()->routeIs('historial-tecnico.*') ? 'active' : '' }}">
                    <i class="bi bi-tools"></i> Mantenimientos
                </a>
            </li>
            @endcan
            
            @canany(['configuracion.editar', 'campos_personalizados.ver'])
            <li class="nav-item">
                @php
                    $isConfigActive = request()->routeIs('tipo-recursos.*', 'campos-personalizados.*');
                @endphp
                <a class="nav-link {{ $isConfigActive ? 'active' : '' }}" href="#menuConfiguracion" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isConfigActive ? 'true' : 'false' }}" aria-controls="menuConfiguracion">
                    <i class="bi bi-gear"></i> <span>Configuración</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem; transition: transform 0.3s;"></i>
                </a>
                <div class="collapse {{ $isConfigActive ? 'show' : '' }}" id="menuConfiguracion" style="visibility: visible;">
                    <ul class="nav flex-column" style="padding-left: 1rem; margin-top: 4px; margin-bottom: 4px;">
                        @can('configuracion.editar')
                        <li class="nav-item">
                            <a href="{{ route('tipo-recursos.index') }}" class="nav-link {{ request()->routeIs('tipo-recursos.*') ? 'active' : '' }}" style="padding-top: 6px; padding-bottom: 6px; margin-bottom: 2px;">
                                <i class="bi bi-tags"></i> <span>Categorías</span>
                            </a>
                        </li>
                        @endcan
                        
                        @can('campos_personalizados.ver')
                        <li class="nav-item">
                            <a href="{{ route('campos-personalizados.index') }}" class="nav-link {{ request()->routeIs('campos-personalizados.*') ? 'active' : '' }}" style="padding-top: 6px; padding-bottom: 6px; margin-bottom: 2px;">
                                <i class="bi bi-ui-checks-grid"></i> <span>Campos Personalizados</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcanany
            
            @can('licencias.ver')
            <li class="nav-item">
                <a href="{{ route('licencias.index') }}" class="nav-link {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}">
                    <i class="bi bi-key"></i> Licencias
                </a>
            </li>
            @endcan


            @can('dashboard.ver')
            <li class="nav-item">
                <a href="{{ route('reportes.index') }}" class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                </a>
            </li>
            @endcan
            
            @can('configuracion.editar')
            <li class="nav-item">
                <a href="{{ route('plantillas-pdf.index') }}" class="nav-link {{ request()->routeIs('plantillas-pdf.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-check"></i> Actas
                </a>
            </li>
            @endcan

            @can('equipos.ver')
            <li class="nav-item">
                <a href="{{ route('actas-firmadas.index') }}" class="nav-link {{ request()->routeIs('actas-firmadas.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> Actas Firmadas
                </a>
            </li>
            @endcan
            
            @can('equipos.importar')
            <li class="nav-item">
                <a href="{{ route('equipos.importar.form') }}" class="nav-link {{ request()->routeIs('equipos.importar*') ? 'active' : '' }}">
                    <i class="bi bi-upload"></i> Importar
                </a>
            </li>
            @endcan
            
            @can('roles.ver')
            <li class="nav-item mt-3 mb-1 px-3 text-uppercase text-muted text-xs font-weight-bold">Seguridad</li>
            <li class="nav-item">
                <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Usuarios Sistema
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i> Roles y Permisos
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('auditoria.index') }}" class="nav-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> Auditoría
                </a>
            </li>
            @endcan
            
            @can('configuracion.editar')
            <li class="nav-item">
                <a href="#" class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                    <i class="bi bi-database-down"></i> Backups
                </a>
            </li>
            @endcan
        </ul>
    </div>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link btn btn-link text-start w-100 btn-logout">
                <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
            </button>
        </form>
    </div>
</nav>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3 overflow-hidden">
            <button class="btn btn-light d-md-none" id="sidebarToggleBtn">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Logo para móvil (Enlace al inicio) -->
            <a href="{{ route('inicio') }}" class="d-md-none" aria-label="Ir al Inicio">
                <img src="{{ asset('imagenes/logo_comite_tolima.png') }}" alt="Logo Comité Tolima" style="height: 40px; object-fit: contain;">
            </a>
            <div class="topbar-title text-truncate d-none d-md-flex">
                <h5 class="mb-0 text-truncate">Sistema Inventario</h5>
                <span class="d-none d-sm-block text-truncate" style="font-size: 0.65rem; font-weight: 600; letter-spacing: 0.2px; color: var(--primary-color);">FEDERACIÓN NACIONAL DE CAFETEROS - TOLIMA</span>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Iconos de accesos rápidos en móvil (Complementos y Préstamos) -->
            <div class="d-md-none d-flex align-items-center gap-3 me-1">
                @can('equipos.ver')
                <a href="{{ route('equipos.complementos.global') }}" class="text-decoration-none" style="color: {{ request()->routeIs('equipos.complementos.global') ? 'var(--primary-color)' : 'var(--text-muted)' }};">
                    <i class="bi bi-box-seam" style="font-size: 1.25rem;"></i>
                </a>
                <a href="{{ route('prestamos.index') }}" class="text-decoration-none" style="color: {{ request()->routeIs('prestamos.*') ? 'var(--primary-color)' : 'var(--text-muted)' }};">
                    <i class="bi bi-calendar2-range" style="font-size: 1.25rem;"></i>
                </a>
                @endcan
            </div>

            <div class="dropdown">
                <div class="user-profile" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                    <div class="user-info d-none d-md-flex text-end">
                        <span class="user-role d-block" style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">{{ auth()->user()->roles->first()->name ?? 'Usuario' }}</span>
                        <span class="user-name d-block" style="font-size: 0.85rem; font-weight: 700; color: var(--text-color);">{{ auth()->user()->name ?? 'ADMIN' }}</span>
                    </div>
                    <div class="user-avatar" style="background: #196f3d; color: white;">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('usuarios.edit', auth()->id()) }}">
                            <i class="bi bi-key text-secondary me-2"></i> Cambiar contraseña
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

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
            <i class="bi bi-display"></i>
            <span>Activos</span>
        </a>
        @endcan
        
        @can('usuarios.ver')
        <a href="{{ route('funcionarios.index') }}" class="bottom-nav-item {{ request()->routeIs('funcionarios.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i>
            <span>Funcionarios</span>
        </a>
        @endcan
        

        
        @can('licencias.ver')
        <a href="{{ route('licencias.index') }}" class="bottom-nav-item {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}">
            <i class="bi bi-key"></i>
            <span>Licencias</span>
        </a>
        @endcan
        
        <!-- Botón MÁS (Abre el overlay) -->
        <a href="#" class="bottom-nav-item" id="btnMobileMore">
            <i class="bi bi-grid"></i>
            <span>Más</span>
        </a>
    </div>
</nav>

<!-- BOTÓN FLOTANTE ESCANEAR (SOLO MÓVIL) -->
@can('equipos.ver')
<button class="scan-fab d-md-none" id="scanFab" data-scan-url="{{ route('equipos.buscar-placa') }}" aria-label="Escanear código de barras" title="Escanear placa">
    <i class="bi bi-upc-scan"></i>
</button>
@endcan
<!-- SCANNER FULLSCREEN (SOLO MÓVIL) -->
<div class="scanner-overlay" id="scannerOverlay">
    <div class="scanner-header">
        <h5><i class="bi bi-upc-scan me-2"></i>Escanear Placa</h5>
        <button class="scanner-close-btn" id="scannerClose" aria-label="Cerrar escáner">&times;</button>
    </div>
    <div class="scanner-viewfinder">
        <video id="scannerVideo" muted playsinline></video>
        <div class="scanner-frame"></div>
        <!-- Resultado -->
        <div class="scanner-result" id="scannerResult">
            <div class="scanner-result-card">
                <div class="scanner-result-icon" id="scannerResultIcon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="scanner-result-title" id="scannerResultTitle">Activo encontrado</div>
                <div class="scanner-result-placa" id="scannerResultPlaca"></div>
                <div class="scanner-result-actions">
                    <button class="btn btn-primary" id="scannerResultGo">
                        <i class="bi bi-eye me-1"></i> Ver detalle del activo
                    </button>
                    <button class="btn btn-outline-secondary" id="scannerRetry">
                        <i class="bi bi-upc-scan me-1"></i> Escanear nuevamente
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
                <i class="bi bi-headset"></i> HelpDesk
            </a>
            @endcan
            
            @can('historial.ver')
            <a href="{{ route('historial-tecnico.index') }}" class="mobile-more-link {{ request()->routeIs('historial-tecnico.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Mantenimientos
            </a>
            @endcan
            
            @can('licencias.ver')
            <a href="{{ route('licencias.index') }}" class="mobile-more-link {{ request()->routeIs('licencias.*') || request()->routeIs('licencia-asignaciones.*') ? 'active' : '' }}">
                <i class="bi bi-key"></i> Licencias
            </a>
            @endcan
            
            @can('dashboard.ver')
            <a href="{{ route('reportes.index') }}" class="mobile-more-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
            </a>
            @endcan
            
            @can('equipos.ver')
            <a href="{{ route('actas-firmadas.index') }}" class="mobile-more-link {{ request()->routeIs('actas-firmadas.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-pdf"></i> Actas Firmadas
            </a>
            @endcan
            
            @can('equipos.importar')
            <a href="{{ route('equipos.importar.form') }}" class="mobile-more-link {{ request()->routeIs('equipos.importar*') ? 'active' : '' }}">
                <i class="bi bi-upload"></i> Importar Datos
            </a>
            @endcan
        </div>
        
        <!-- Sección Configuración -->
        @canany(['configuracion.editar', 'campos_personalizados.ver'])
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Configuración</div>
            
            @can('configuracion.editar')
            <a href="{{ route('tipo-recursos.index') }}" class="mobile-more-link {{ request()->routeIs('tipo-recursos.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Categorías
            </a>
            @endcan
            
            @can('campos_personalizados.ver')
            <a href="{{ route('campos-personalizados.index') }}" class="mobile-more-link {{ request()->routeIs('campos-personalizados.*') ? 'active' : '' }}">
                <i class="bi bi-ui-checks-grid"></i> Campos Personalizados
            </a>
            @endcan
            
            @can('configuracion.editar')
            <a href="{{ route('plantillas-pdf.index') }}" class="mobile-more-link {{ request()->routeIs('plantillas-pdf.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-check"></i> Plantillas de Actas
            </a>
            @endcan
        </div>
        @endcanany

        <!-- Sección Seguridad -->
        @can('roles.ver')
        <div class="mobile-more-section">
            <div class="mobile-more-section-title">Seguridad</div>
            
            <a href="{{ route('usuarios.index') }}" class="mobile-more-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Usuarios Sistema
            </a>
            <a href="{{ route('roles.index') }}" class="mobile-more-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Roles y Permisos
            </a>
            <a href="{{ route('auditoria.index') }}" class="mobile-more-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Auditoría
            </a>
            @can('configuracion.editar')
            <a href="#" class="mobile-more-link {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                <i class="bi bi-database-down"></i> Backups
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
                        <div style="font-size:0.75rem; color:var(--text-muted);">{{ auth()->user()->roles->first()->name ?? 'Usuario' }}</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mobile-logout-form">
                @csrf
                <button type="submit" class="mobile-logout-btn">
                    <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
                </button>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/app-core.js') }}?v={{ time() }}"></script>
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

        if (sidebar && window.matchMedia('(min-width: 769px)').matches) {
            sidebar.classList.remove('is-expanded');

            sidebar.addEventListener('mouseenter', () => {
                sidebar.classList.add('is-expanded');
            });

            sidebar.addEventListener('mouseleave', () => {
                sidebar.classList.remove('is-expanded');
            });
        }
    });
</script>
@stack('scripts')
    <!-- Validaciones en tiempo real -->
    <script src="{{ asset('js/realtime-validations.js') }}?v={{ time() }}"></script>
    <!-- Lector de código de barras (solo móvil) -->
    <script src="{{ asset('js/barcode-scanner.js') }}?v={{ time() }}"></script>
</body>
</html>
