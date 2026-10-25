@extends('layouts.inventario')

@section('title', 'Correcciones de importación')

@section('content')
@push('styles')
<style>
    /* Ficha del activo dentro del modal de corrección. */
    .fnc-ficha__sec {
        border: 1px solid var(--bs-border-color);
        border-radius: .7rem;
        padding: .6rem .8rem;
        margin-bottom: .6rem;
        background: var(--bs-body-bg);
    }
    .fnc-ficha__title {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        margin-bottom: .35rem;
    }
    .fnc-ficha dt {
        font-weight: 600;
        font-size: .75rem;
        color: var(--bs-secondary-color);
    }
    .fnc-ficha dd {
        font-size: .8rem;
        margin-bottom: .15rem;
        word-break: break-word;
    }
</style>
@endpush
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Correcciones"
        icon="clipboard-pulse"
        subtitle="Importación no condicionada: cada faltante del CMDB queda aquí. Organiza el trabajo por campo (con acciones en lote) o por activo."
    >
        <form method="POST" action="{{ route('correcciones.regenerar', ['_tab' => request('_tab')]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary" title="Re-escanea todos los activos y regenera los pendientes">
                <i class="bi bi-arrow-repeat me-1"></i> Regenerar pendientes
            </button>
        </form>
    </x-ui.toolbar>

    {{-- Vistas de trabajo --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $filtros['vista'] === 'campos' ? 'active' : '' }}"
               href="{{ route('correcciones.index', ['vista' => 'campos', '_tab' => request('_tab')]) }}">
                <i class="bi bi-list-check me-1"></i> Por campo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filtros['vista'] === 'activos' ? 'active' : '' }}"
               href="{{ route('correcciones.index', ['vista' => 'activos', '_tab' => request('_tab')]) }}">
                <i class="bi bi-laptop me-1"></i> Por activo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filtros['vista'] === 'lista' ? 'active' : '' }}"
               href="{{ route('correcciones.index', ['vista' => 'lista', '_tab' => request('_tab')]) }}">
                <i class="bi bi-list-ul me-1"></i> Lista completa
                @if($stats['pendiente'] > 0)
                    <span class="badge text-bg-danger ms-1">{{ number_format($stats['pendiente'], 0, ',', '.') }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item ms-lg-auto">
            <a class="nav-link" href="{{ route('equipos.importar.form') }}">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar
            </a>
        </li>
    </ul>

    {{-- Semáforo de uniformidad --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Pendientes</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($stats['pendiente'], 0, ',', '.') }}</div>
                <div class="text-muted small">{{ $stats['alta'] }} de severidad alta</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Resueltas</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($stats['resuelta'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Justificadas (N/A)</div>
                <div class="fs-4 fw-bold text-secondary">{{ number_format($stats['ignorada'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Información completa</div>
                <div class="fs-4 fw-bold">{{ $stats['avance'] }}%</div>
                <div class="progress mt-1" style="height:6px;">
                    <div class="progress-bar bg-success" style="width: {{ $stats['avance'] }}%"></div>
                </div>
            </div></div>
        </div>
    </div>

    {{-- ════════════ VISTA 1: FRENTES DE TRABAJO POR CAMPO ════════════ --}}
    @if($filtros['vista'] === 'campos' && $porCampo !== null)
        <x-ui.filter-container action="{{ route('correcciones.index', ['vista' => 'campos']) }}">
            <div class="col-12 col-lg-4">
                <label class="form-label fw-medium small mb-1">Buscar campo</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="{{ $filtros['q'] }}" class="form-control"
                           placeholder="Ej.: garantía, shortname, remisión..." autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label fw-medium small mb-1">Severidad</label>
                <select name="severidad" class="form-select">
                    <option value="">Todas</option>
                    <option value="alta" {{ $filtros['severidad'] === 'alta' ? 'selected' : '' }}>Alta</option>
                    <option value="media" {{ $filtros['severidad'] === 'media' ? 'selected' : '' }}>Media</option>
                    <option value="baja" {{ $filtros['severidad'] === 'baja' ? 'selected' : '' }}>Baja</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label fw-medium small mb-1">Sección</label>
                <select name="seccion" class="form-select">
                    <option value="">Todas</option>
                    <option value="identidad" {{ $filtros['seccion'] === 'identidad' ? 'selected' : '' }}>Identidad</option>
                    <option value="ficha" {{ $filtros['seccion'] === 'ficha' ? 'selected' : '' }}>Ficha CMDB</option>
                    <option value="catalogos" {{ $filtros['seccion'] === 'catalogos' ? 'selected' : '' }}>Catálogos</option>
                    <option value="custodia" {{ $filtros['seccion'] === 'custodia' ? 'selected' : '' }}>Custodia</option>
                </select>
            </div>
        </x-ui.filter-container>

        <x-ui.card noPadding="true">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th class="ps-4">Frente de trabajo</th>
                        <th>Pendientes</th>
                        <th>Valor actual (ejemplo)</th>
                        <th class="text-end pe-4">Acciones en lote</th>
                    </tr>
                </x-slot:head>

                @forelse($porCampo as $frente)
                    <tr>
                        <td class="ps-4" style="min-width: 260px;">
                            <div class="fnc-udash__names">
                                <span class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fnc-udash__name">{{ $frente->etiqueta }}</span>
                                    <span class="badge rounded-pill {{ $frente->severidad === 'alta' ? 'text-bg-danger' : ($frente->severidad === 'media' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ ucfirst($frente->severidad) }}</span>
                                    <span class="badge rounded-pill text-bg-light border">{{ $frente->seccion }}</span>
                                </span>
                                <span class="fnc-udash__sub">{{ $frente->campo }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="fs-5 fw-bold {{ $frente->pendientes > 100 ? 'text-danger' : ($frente->pendientes > 20 ? 'text-warning' : 'text-secondary') }}">
                                {{ number_format($frente->pendientes, 0, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="fnc-udash__sub text-truncate d-block" style="max-width: 200px;" title="{{ $frente->ejemplo }}">
                                {{ $frente->ejemplo ?: '(vacío)' }}
                            </span>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            @can('equipos.editar')
                                @if($frente->inferibles ?? 0)
                                    <form method="POST" action="{{ route('correcciones.sugerir', ['_tab' => request('_tab')]) }}" class="d-inline"
                                          onsubmit="return confirm('¿Completar con el dato más común de sus equipos hermanos (misma marca y modelo) los pendientes de inferencia sólida de este frente? Los que no tengan inferencia quedarán pendientes.');">
                                        @csrf
                                        <input type="hidden" name="campo" value="{{ $frente->campo }}">
                                        <button type="submit" class="btn btn-sm btn-success me-1"
                                                title="Inferencia por equipos similares: ~{{ $frente->inferibles }} de {{ $frente->pendientes }} tienen hermanos con el dato">
                                            <i class="bi bi-magic"></i><span class="d-none d-xxl-inline ms-1">Sugerencias ({{ $frente->inferibles }})</span>
                                        </button>
                                    </form>
                                @endif
                                @if(!str_starts_with($frente->campo, 'custodia.'))
                                    <button type="button" class="btn btn-sm btn-primary me-1"
                                            data-masivo-corregir
                                            data-campo="{{ $frente->campo }}"
                                            data-etiqueta="{{ $frente->etiqueta }}"
                                            data-count="{{ $frente->pendientes }}"
                                            data-tipo="{{ $frente->tipo_dato }}"
                                            data-catalogo="{{ $frente->catalogo }}"
                                            title="Aplicar el mismo valor a los {{ $frente->pendientes }} pendientes">
                                        <i class="bi bi-pencil-square"></i><span class="d-none d-xxl-inline ms-1">Corregir todos</span>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                        data-masivo-ignorar
                                        data-campo="{{ $frente->campo }}"
                                        data-etiqueta="{{ $frente->etiqueta }}"
                                        data-count="{{ $frente->pendientes }}"
                                        title="Justificar (N/A) los {{ $frente->pendientes }} pendientes">
                                    <i class="bi bi-slash-circle"></i><span class="d-none d-xxl-inline ms-1">Justificar todos</span>
                                </button>
                            @endcan
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('correcciones.index', ['vista' => 'lista', 'campo' => $frente->campo, '_tab' => request('_tab')]) }}"
                               title="Ver los {{ $frente->pendientes }} pendientes uno a uno">
                                <i class="bi bi-list-ul"></i><span class="d-none d-xxl-inline ms-1">Ver</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-check fs-2 d-block mb-2 opacity-50"></i>
                            Sin frentes con estos filtros. Si acabas de importar, pulsa «Regenerar pendientes».
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <div class="text-muted small mt-2">
            <i class="bi bi-lightbulb me-1"></i>
            «Corregir todos» aplica el MISMO valor al frente completo (útil para catálogos: estado de uso, administrado, tipo…).
            «Justificar todos» archiva el frente con una causa (N/A justificado).
        </div>
    @endif

    {{-- ════════════ VISTA 2: POR ACTIVO (PEORES FICHAS PRIMERO) ════════════ --}}
    @if($filtros['vista'] === 'activos' && $porActivo !== null)
        <x-ui.card noPadding="true">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th class="ps-4">Activo</th>
                        <th>Pendientes</th>
                        <th>Severidad alta</th>
                        <th class="text-end pe-4">Acción</th>
                    </tr>
                </x-slot:head>

                @forelse($porActivo as $fila)
                    <tr>
                        <td class="ps-4">
                            <div class="fnc-udash__names">
                                <span class="fnc-udash__name">{{ $fila->equipo?->nombre_visible ?: 'Activo #' . $fila->equipo_id }}</span>
                                <span class="fnc-udash__sub">
                                    {{ $fila->equipo?->placa ?: 'sin placa' }} · {{ $fila->equipo?->serial ?: 'sin serial' }} · {{ $fila->equipo?->tipoRecurso?->nombre }}
                                </span>
                            </div>
                        </td>
                        <td><span class="fs-5 fw-bold {{ $fila->pendientes > 10 ? 'text-danger' : 'text-warning' }}">{{ $fila->pendientes }}</span></td>
                        <td>{!! $fila->altas > 0 ? '<span class="badge text-bg-danger">'.$fila->altas.'</span>' : '<span class="text-muted">—</span>' !!}</td>
                        <td class="text-end pe-4">
                            <a class="btn btn-sm btn-primary"
                               href="{{ route('correcciones.index', ['vista' => 'lista', 'equipo_id' => $fila->equipo_id, '_tab' => request('_tab')]) }}">
                                Completar ficha <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted">Sin pendientes por activo.</td></tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    @endif

    {{-- ════════════ VISTA 3: LISTA COMPLETA (FILA A FILA) ════════════ --}}
    @if($filtros['vista'] === 'lista' && $correcciones !== null)
        @if($filtros['campo'] || $filtros['equipo_id'])
            <div class="alert alert-info d-flex align-items-center justify-content-between py-2">
                <div>
                    @if($filtros['campo'])
                        <i class="bi bi-funnel me-2"></i>Filtrado por campo <strong>{{ $filtros['campo'] }}</strong>
                    @else
                        <i class="bi bi-funnel me-2"></i>Filtrado por <strong>un activo</strong> (completar su ficha)
                    @endif
                </div>
                <a href="{{ route('correcciones.index', ['vista' => 'lista', '_tab' => request('_tab')]) }}" class="btn btn-sm btn-outline-secondary">Quitar filtro</a>
            </div>
        @endif

        <x-ui.filter-container action="{{ route('correcciones.index', ['vista' => 'lista']) }}">
            <div class="col-12 col-lg-4">
                <label class="form-label fw-medium small mb-1">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="{{ $filtros['q'] }}" class="form-control"
                           placeholder="Campo, valor, placa, serial, nombre del activo..." autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label fw-medium small mb-1">Estado</label>
                <select name="estado" class="form-select">
                    <option value="pendiente" {{ $filtros['estado'] === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                    <option value="resuelta" {{ $filtros['estado'] === 'resuelta' ? 'selected' : '' }}>Resueltas</option>
                    <option value="ignorada" {{ $filtros['estado'] === 'ignorada' ? 'selected' : '' }}>Justificadas</option>
                    <option value="todas" {{ $filtros['estado'] === 'todas' ? 'selected' : '' }}>Todas</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label fw-medium small mb-1">Severidad</label>
                <select name="severidad" class="form-select">
                    <option value="">Todas</option>
                    <option value="alta" {{ $filtros['severidad'] === 'alta' ? 'selected' : '' }}>Alta</option>
                    <option value="media" {{ $filtros['severidad'] === 'media' ? 'selected' : '' }}>Media</option>
                    <option value="baja" {{ $filtros['severidad'] === 'baja' ? 'selected' : '' }}>Baja</option>
                </select>
            </div>
            <input type="hidden" name="campo" value="{{ $filtros['campo'] }}">
            <input type="hidden" name="equipo_id" value="{{ $filtros['equipo_id'] }}">
        </x-ui.filter-container>

        <x-ui.card noPadding="true">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th class="ps-4">Activo</th>
                        <th>Campo faltante</th>
                        <th>Valor actual</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </x-slot:head>

                @php $fichas = []; @endphp
                @forelse($correcciones as $correccion)
                    @php
                        $equipo = $correccion->equipo;
                        $ficha = $equipo ? ($fichas[$equipo->id] ??= $equipo->fichaParaCorreccion()) : null;
                    @endphp
                    <tr>
                        <td class="ps-4" style="min-width: 170px;">
                            @if($equipo)
                                <div class="fnc-udash__names">
                                    <span class="fnc-udash__name text-truncate" style="max-width: 220px;" title="{{ $equipo->nombre_visible }}">
                                        {{ $equipo->nombre_visible }}
                                    </span>
                                    <span class="fnc-udash__sub text-truncate" style="max-width: 220px;"
                                          title="{{ $equipo->placa }} · {{ $equipo->serial }} · {{ $equipo->tipoRecurso?->nombre }} · Fila CMDB {{ $equipo->fila_cmdb ?? '—' }}">
                                        {{ $equipo->placa ?: 'sin placa' }} · {{ $equipo->tipoRecurso?->nombre }} · Fila {{ $equipo->fila_cmdb ?? '—' }}
                                    </span>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td style="min-width: 220px;">
                            <div class="fnc-udash__names">
                                <span class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fnc-udash__name">{{ $correccion->etiqueta }}</span>
                                    <span class="badge rounded-pill {{ $correccion->severidad === 'alta' ? 'text-bg-danger' : ($correccion->severidad === 'media' ? 'text-bg-warning' : 'text-bg-secondary') }}"
                                          title="Severidad {{ $correccion->severidad_label }}">{{ $correccion->severidad_label }}</span>
                                </span>
                                <span class="fnc-udash__sub">{{ $correccion->mensaje }}</span>
                            </div>
                        </td>
                        <td style="min-width: 130px; max-width: 260px;">
                            @if($correccion->estado === 'resuelta')
                                <span class="fnc-udash__sub d-block text-truncate" title="Antes: {{ $correccion->valor_actual ?: '(vacío)' }}">Antes: {{ $correccion->valor_actual ?: '(vacío)' }}</span>
                                <span class="fnc-udash__name text-success d-block text-truncate" title="{{ $correccion->valor_corregido }}">
                                    {{ $correccion->valor_corregido ?: '✓' }}@if($correccion->origen === 'inferido') <span class="badge text-bg-info" title="Completado por inferencia de equipos similares (misma marca y modelo)">inferido</span>@endif
                                </span>
                            @elseif($correccion->estado === 'ignorada')
                                <span class="fnc-udash__sub d-block text-truncate" title="{{ $correccion->valor_actual ?: '(vacío)' }} — {{ $correccion->justificacion }}">
                                    {{ $correccion->valor_actual ?: '(vacío)' }} — justificado
                                </span>
                            @else
                                <span class="fnc-udash__sub d-block text-truncate" title="{{ $correccion->valor_actual }}">{{ $correccion->valor_actual ?: '(vacío)' }}</span>
                                @if(($sugerencias[$correccion->id] ?? null))
                                    @php $sug = $sugerencias[$correccion->id]; @endphp
                                    @if($sug['n'] >= \App\Services\CorreccionService::MIN_HERMANOS && $sug['confianza'] >= \App\Services\CorreccionService::MIN_CONFIANZA)
                                        <form method="POST" action="{{ route('correcciones.sugerir.individual', ['correccion' => $correccion->id, '_tab' => request('_tab')]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success py-0 px-1"
                                                    title="Aplicar el dato más común de sus {{ $sug['n'] }} hermanos ({{ $sug['nivel'] }})">
                                                <i class="bi bi-magic me-1"></i>{{ $sug['valor'] }} <span class="text-nowrap">({{ $sug['n'] }} iguales)</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="fnc-udash__sub d-block" title="Inferencia débil: no se aplica automáticamente">
                                            <i class="bi bi-magic"></i> {{ $sug['valor'] }} ({{ $sug['n'] }}, {{ (int) round($sug['confianza'] * 100) }}%)
                                        </span>
                                    @endif
                                @endif
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <x-fnc.pill :tone="$correccion->estado_tone" dot>{{ ucfirst($correccion->estado) }}</x-fnc.pill>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            @if($correccion->estado === 'pendiente')
                                @can('equipos.editar')
                                    <button type="button" class="btn btn-sm btn-primary me-1"
                                            data-corregir
                                            data-id="{{ $correccion->id }}"
                                            data-etiqueta="{{ $correccion->etiqueta }}"
                                            data-actual="{{ $correccion->valor_actual }}"
                                            data-tipo="{{ $correccion->tipo_dato }}"
                                            data-catalogo="{{ $correccion->catalogo }}"
                                            data-severidad="{{ $correccion->severidad }}"
                                            data-severidad-label="{{ $correccion->severidad_label }}"
                                            data-mensaje="{{ $correccion->mensaje }}"
                                            data-activo="{{ $equipo?->identificador_interno ?: 'activo' }}"
                                            data-ficha="{{ json_encode($ficha, JSON_UNESCAPED_UNICODE) }}"
                                            title="Corregir «{{ $correccion->etiqueta }}»" aria-label="Corregir {{ $correccion->etiqueta }}">
                                        <i class="bi bi-pencil-square"></i><span class="ms-1">Corregir</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-ignorar data-id="{{ $correccion->id }}"
                                            data-etiqueta="{{ $correccion->etiqueta }}"
                                            title="Justificar como N/A" aria-label="Justificar {{ $correccion->etiqueta }}">
                                        <i class="bi bi-slash-circle"></i><span class="ms-1">Justificar</span>
                                    </button>
                                @endcan
                            @else
                                <span class="fnc-udash__sub">{{ $correccion->resuelta_en?->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-check fs-2 d-block mb-2 opacity-50"></i>
                            No hay correcciones con estos filtros. Si acabas de importar, pulsa «Regenerar pendientes».
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>

            @if($correcciones->hasPages())
                <x-slot name="footer">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                        <small class="text-muted">
                            Mostrando {{ $correcciones->firstItem() }}–{{ $correcciones->lastItem() }} de {{ number_format($correcciones->total(), 0, ',', '.') }} correcciones
                        </small>
                        {{ $correcciones->links() }}
                    </div>
                </x-slot>
            @endif
        </x-ui.card>
    @endif
</div>

{{-- ── Modal de corrección individual ───────────────────────────────── --}}
<div class="modal fade" id="modalCorregir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('correcciones.resolver', ['correccion' => 'ID', '_tab' => request('_tab')]) }}" id="formCorregir" class="modal-content">
            @csrf
            <div class="modal-header align-items-start">
                <div class="pe-3">
                    <h5 class="modal-title mb-1">Corregir: <span id="corregirEtiqueta"></span></h5>
                    <div class="small text-muted" id="corregirActivoLinea"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small py-2 px-3 mb-3" id="corregirContexto"></div>
                <div id="corregirCampoWrapper" class="mb-3">
                    <label class="form-label fw-medium" for="corregirValor">Valor real</label>
                    <input type="text" name="valor" id="corregirValor" class="form-control" required>
                </div>
                <div class="form-text text-muted mb-3">La corrección se aplica directamente al registro del activo.</div>

                {{-- Ficha completa: identifica sin ambigüedad el equipo a corregir. --}}
                <div class="fnc-ficha" id="corregirFicha"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar corrección</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal de justificación individual ────────────────────────────── --}}
<div class="modal fade" id="modalIgnorar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('correcciones.ignorar', ['correccion' => 'ID', '_tab' => request('_tab')]) }}" id="formIgnorar" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Justificar: <span id="ignorarEtiqueta"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-medium" for="justificacion">¿Por qué no aplica este dato? (mínimo 5 caracteres)</label>
                <textarea name="justificacion" id="justificacion" class="form-control" rows="3" required
                          placeholder="Ej: el activo es una impresora y no tiene nombre de equipo."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-secondary"><i class="bi bi-slash-circle me-1"></i> Archivar como justificado</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal MASIVO: corregir un frente de trabajo ───────────────────── --}}
<div class="modal fade" id="modalMasivoCorregir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('correcciones.masivo', ['_tab' => request('_tab')]) }}" id="formMasivoCorregir" class="modal-content">
            @csrf
            <input type="hidden" name="modo" value="resolver">
            <input type="hidden" name="campo" id="masivoCorregirCampo">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title text-warning-emphasis"><i class="bi bi-exclamation-triangle me-2"></i>Corregir en lote: <span id="masivoCorregirEtiqueta"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small">
                    Se aplicará el MISMO valor a <strong><span id="masivoCorregirCount"></span> pendientes</strong> de este campo.
                    Úsalo cuando el valor real es el mismo para todos (catálogos, marcas, responsables TI…).
                </p>
                <div id="masivoCorregirWrapper">
                    <label class="form-label fw-medium" for="masivoCorregirValor">Valor a aplicar</label>
                    <input type="text" name="valor" id="masivoCorregirValor" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i> Aplicar a los <span class="masivoCountInline"></span></button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal MASIVO: justificar un frente de trabajo ──────────────────── --}}
<div class="modal fade" id="modalMasivoIgnorar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('correcciones.masivo', ['_tab' => request('_tab')]) }}" id="formMasivoIgnorar" class="modal-content">
            @csrf
            <input type="hidden" name="modo" value="ignorar">
            <input type="hidden" name="campo" id="masivoIgnorarCampo">
            <div class="modal-header">
                <h5 class="modal-title">Justificar en lote: <span id="masivoIgnorarEtiqueta"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small">
                    Se archivarán como N/A justificado <strong><span id="masivoIgnorarCount"></span> pendientes</strong> de este campo,
                    con la misma causa.
                </p>
                <label class="form-label fw-medium" for="masivoIgnorarJustificacion">Causa (mínimo 5 caracteres)</label>
                <textarea name="justificacion" id="masivoIgnorarJustificacion" class="form-control" rows="3" required
                          placeholder="Ej: documento físico que no se diligencia en el CMDB."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-secondary"><i class="bi bi-slash-circle me-1"></i> Archivar <span class="masivoCountInline"></span></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var TAB = (function () {
        var t = new URLSearchParams(window.location.search).get('_tab');
        return t ? '?_tab=' + encodeURIComponent(t) : '';
    })();

    var modalCorregir = null, modalIgnorar = null, modalMasivoCorregir = null, modalMasivoIgnorar = null;

    function inputPara(wrapperId, tipo, catalogo, required, fechaLabel) {
        var wrapper = document.getElementById(wrapperId);
        if (tipo === 'fecha') {
            wrapper.innerHTML = '<label class="form-label fw-medium" for="' + wrapperId + 'Valor">' + (fechaLabel || 'Fecha real (dd/mm/aaaa)') + '</label>' +
                '<input type="date" name="valor" id="' + wrapperId + 'Valor" class="form-control"' + (required ? ' required' : '') + '>';
        } else if (tipo === 'catalogo' && catalogo) {
            wrapper.innerHTML = '<label class="form-label fw-medium" for="' + wrapperId + 'Valor">Valor del catálogo oficial</label>' +
                '<select name="valor" id="' + wrapperId + 'Valor" class="form-select"' + (required ? ' required' : '') + '>' +
                '<option value="">Cargando opciones…</option></select>';
        } else {
            wrapper.innerHTML = '<label class="form-label fw-medium" for="' + wrapperId + 'Valor">Valor real</label>' +
                '<input type="text" name="valor" id="' + wrapperId + 'Valor" class="form-control"' + (required ? ' required' : '') + '>';
        }
        return document.getElementById(wrapperId + 'Valor');
    }

    /* El widget fnc-combobox transforma el select apenas se inserta (con el
       placeholder «Cargando opciones…»); cuando el fetch llena las opciones
       hay que re-sincronizarlo para que el input visible y el panel reflejen
       el catálogo real. */
    function sincronizarCombobox(select) {
        if (window.FNCCombobox && select) {
            if (select.__fncSt) window.FNCCombobox.sync(select);
            else window.FNCCombobox.enhance(select);
        }
    }

    function cargarOpcionesPorId(select, correccionId) {
        select.innerHTML = '<option value="">Cargando opciones…</option>';
        fetch('/correcciones/' + correccionId + '/opciones' + TAB, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                select.innerHTML = '<option value="">Selecciona…</option>';
                (data.opciones || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.valor; opt.textContent = o.etiqueta;
                    select.appendChild(opt);
                });
                sincronizarCombobox(select);
            })
            .catch(function () { select.innerHTML = '<option value="">No se pudieron cargar las opciones</option>'; sincronizarCombobox(select); });
    }

    function cargarOpcionesPorCatalogo(select, catalogo) {
        select.innerHTML = '<option value="">Cargando opciones…</option>';
        fetch('/correcciones/opciones/' + encodeURIComponent(catalogo) + TAB, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                select.innerHTML = '<option value="">Selecciona…</option>';
                (data.opciones || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.valor; opt.textContent = o.etiqueta;
                    select.appendChild(opt);
                });
                if (!(data.opciones || []).length) {
                    select.innerHTML = '<option value="">El catálogo no tiene opciones</option>';
                }
                sincronizarCombobox(select);
            })
            .catch(function () { select.innerHTML = '<option value="">No se pudieron cargar las opciones</option>'; sincronizarCombobox(select); });
    }

    /* ── Ficha del activo en el modal de corrección ─────────────────── */

    function esc(valor) {
        return String(valor === null || valor === undefined ? '' : valor).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var SECCIONES_FICHA = [
        ['identificacion', 'Identificación'],
        ['estado', 'Estado'],
        ['tecnica', 'Ficha técnica'],
        ['cmdb', 'Datos del CMDB'],
        ['custodia', 'Custodia actual']
    ];

    function renderFicha(ficha) {
        var cont = document.getElementById('corregirFicha');
        if (!ficha) { cont.innerHTML = ''; return; }

        var html = '';
        SECCIONES_FICHA.forEach(function (par) {
            var seccion = par[0], titulo = par[1];
            if (!(seccion in ficha)) return;
            var datos = ficha[seccion];

            html += '<div class="fnc-ficha__sec"><div class="fnc-ficha__title">' + esc(titulo) + '</div>';
            if (!datos) {
                html += '<div class="text-muted small">Sin custodio asignado.</div>';
            } else {
                html += '<dl class="row g-0 mb-0">';
                Object.keys(datos).forEach(function (etiqueta) {
                    var valor = datos[etiqueta];
                    html += '<dt class="col-5 col-sm-4">' + esc(etiqueta) + '</dt>'
                          + '<dd class="col-7 col-sm-8">'
                          + (valor === null || valor === '' ? '<span class="text-muted">—</span>' : esc(valor))
                          + '</dd>';
                });
                html += '</dl>';
            }
            html += '</div>';
        });
        cont.innerHTML = html;
    }

    function renderContexto(btn, ficha) {
        var ident = (ficha && ficha.identificacion) ? ficha.identificacion : {};
        var linea = [];

        if (ident['Nombre de equipo'] && !/^(PENDIENTE|SIN NOMBRE|SIN ASIGNAR|N\/A|NA|NO APLICA|-)$/i.test(ident['Nombre de equipo'].trim())) {
            linea.push('<strong>' + esc(ident['Nombre de equipo']) + '</strong>');
        }
        if (ident['Activo']) linea.push(esc(ident['Activo']));
        if (ident['Placa']) linea.push('Placa ' + esc(ident['Placa']));
        if (ident['Serial']) linea.push('Serial ' + esc(ident['Serial']));
        if (ident['Fila del CMDB']) linea.push('<span class="badge text-bg-light border">' + esc(ident['Fila del CMDB']) + '</span>');

        document.getElementById('corregirActivoLinea').innerHTML = linea.length
            ? linea.join(' · ')
            : esc(btn.dataset.activo || 'Activo');

        var severidad = btn.dataset.severidad || 'baja';
        var tono = severidad === 'alta' ? 'danger' : (severidad === 'media' ? 'warning' : 'secondary');
        var html = '<div class="d-flex flex-wrap align-items-center gap-2 mb-1">'
            + '<span class="badge text-bg-' + tono + '">' + esc(btn.dataset.severidadLabel || severidad) + '</span>'
            + '<span class="text-muted">Valor actual:</span> <code>' + esc(btn.dataset.actual || '(vacío)') + '</code>'
            + '</div>';
        if (btn.dataset.mensaje) {
            html += '<div class="text-muted">' + esc(btn.dataset.mensaje) + '</div>';
        }
        document.getElementById('corregirContexto').innerHTML = html;
    }

    document.addEventListener('click', function (ev) {
        var btnCorregir = ev.target.closest('[data-corregir]');
        var btnIgnorar   = ev.target.closest('[data-ignorar]');
        var btnMasivoCorregir = ev.target.closest('[data-masivo-corregir]');
        var btnMasivoIgnorar  = ev.target.closest('[data-masivo-ignorar]');

        /* ── Corrección individual ── */
        if (btnCorregir) {
            var id = btnCorregir.dataset.id;
            document.getElementById('corregirEtiqueta').textContent = btnCorregir.dataset.etiqueta;
            document.getElementById('formCorregir').action = '/correcciones/' + id + '/resolver' + TAB;

            var ficha = null;
            try {
                ficha = JSON.parse(btnCorregir.dataset.ficha || 'null');
            } catch (e) {
                ficha = null;
            }
            renderFicha(ficha);
            renderContexto(btnCorregir, ficha);

            var input = inputPara('corregirCampoWrapper', btnCorregir.dataset.tipo, btnCorregir.dataset.catalogo, true);
            if (btnCorregir.dataset.tipo === 'catalogo' && btnCorregir.dataset.catalogo) {
                cargarOpcionesPorId(input, id);
            }

            if (!modalCorregir) modalCorregir = new bootstrap.Modal('#modalCorregir');
            modalCorregir.show();
        }

        /* ── Justificación individual ── */
        if (btnIgnorar) {
            document.getElementById('ignorarEtiqueta').textContent = btnIgnorar.dataset.etiqueta;
            document.getElementById('formIgnorar').action = '/correcciones/' + btnIgnorar.dataset.id + '/ignorar' + TAB;
            if (!modalIgnorar) modalIgnorar = new bootstrap.Modal('#modalIgnorar');
            modalIgnorar.show();
        }

        /* ── Masivo: corregir un frente ── */
        if (btnMasivoCorregir) {
            document.getElementById('masivoCorregirCampo').value = btnMasivoCorregir.dataset.campo;
            document.getElementById('masivoCorregirEtiqueta').textContent = btnMasivoCorregir.dataset.etiqueta;
            document.getElementById('masivoCorregirCount').textContent = btnMasivoCorregir.dataset.count;
            document.querySelectorAll('.masivoCountInline').forEach(function (el) { el.textContent = btnMasivoCorregir.dataset.count; });

            var inputM = inputPara('masivoCorregirWrapper', btnMasivoCorregir.dataset.tipo, btnMasivoCorregir.dataset.catalogo, true);
            if (btnMasivoCorregir.dataset.tipo === 'catalogo' && btnMasivoCorregir.dataset.catalogo) {
                cargarOpcionesPorCatalogo(inputM, btnMasivoCorregir.dataset.catalogo);
            }

            if (!modalMasivoCorregir) modalMasivoCorregir = new bootstrap.Modal('#modalMasivoCorregir');
            modalMasivoCorregir.show();
        }

        /* ── Masivo: justificar un frente ── */
        if (btnMasivoIgnorar) {
            document.getElementById('masivoIgnorarCampo').value = btnMasivoIgnorar.dataset.campo;
            document.getElementById('masivoIgnorarEtiqueta').textContent = btnMasivoIgnorar.dataset.etiqueta;
            document.getElementById('masivoIgnorarCount').textContent = btnMasivoIgnorar.dataset.count;
            document.querySelectorAll('.masivoCountInline').forEach(function (el) { el.textContent = btnMasivoIgnorar.dataset.count; });

            if (!modalMasivoIgnorar) modalMasivoIgnorar = new bootstrap.Modal('#modalMasivoIgnorar');
            modalMasivoIgnorar.show();
        }
    });
})();
</script>
@endpush
@endsection
