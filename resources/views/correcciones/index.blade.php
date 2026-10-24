@extends('layouts.inventario')

@section('title', 'Correcciones de importación')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Correcciones"
        icon="clipboard-pulse"
        subtitle="Importación no condicionada: cada faltante del CMDB queda aquí y se corrige directamente"
    >
        <form method="POST" action="{{ route('correcciones.regenerar', ['_tab' => request('_tab')]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary" title="Re-escanea todos los activos y regenera los pendientes">
                <i class="bi bi-arrow-repeat me-1"></i> Regenerar pendientes
            </button>
        </form>
    </x-ui.toolbar>

    {{-- Pestañas del módulo Importar --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('equipos.importar.form') }}">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar archivo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('correcciones.index') }}">
                <i class="bi bi-clipboard-pulse me-1"></i> Correcciones
                @if($stats['pendiente'] > 0)
                    <span class="badge text-bg-danger ms-1">{{ $stats['pendiente'] }}</span>
                @endif
            </a>
        </li>
    </ul>

    {{-- Semáforo de uniformidad --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Pendientes</div>
                <div class="fs-4 fw-bold text-danger">{{ $stats['pendiente'] }}</div>
                <div class="text-muted small">{{ $stats['alta'] }} de severidad alta</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Resueltas</div>
                <div class="fs-4 fw-bold text-success">{{ $stats['resuelta'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:.08em">Justificadas (N/A)</div>
                <div class="fs-4 fw-bold text-secondary">{{ $stats['ignorada'] }}</div>
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

    {{-- Filtros --}}
    <x-ui.filter-container action="{{ route('correcciones.index') }}">
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

    {{-- Listado de pendientes --}}
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

            @forelse($correcciones as $correccion)
                @php $equipo = $correccion->equipo; @endphp
                <tr>
                    {{-- Activo: nombre + referencia, apilado y con truncado --}}
                    <td class="ps-4" style="min-width: 170px;">
                        @if($equipo)
                            <div class="fnc-udash__names">
                                <span class="fnc-udash__name text-truncate" style="max-width: 220px;" title="{{ $equipo->nombre_equipo }}">
                                    {{ $equipo->nombre_equipo ?: 'Activo #' . $equipo->id }}
                                </span>
                                <span class="fnc-udash__sub text-truncate" style="max-width: 220px;"
                                      title="{{ $equipo->placa }} · {{ $equipo->serial }} · {{ $equipo->tipoRecurso?->nombre }}">
                                    {{ $equipo->placa ?: 'sin placa' }} · {{ $equipo->tipoRecurso?->nombre }}
                                </span>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Campo faltante: etiqueta + severidad compacta + mensaje --}}
                    <td style="min-width: 220px;">
                        <div class="fnc-udash__names">
                            <span class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fnc-udash__name">{{ $correccion->etiqueta }}</span>
                                <span class="badge rounded-pill {{ $correccion->severidad === 'alta' ? 'text-bg-danger' : ($correccion->severidad === 'media' ? 'text-bg-warning' : 'text-bg-secondary') }}"
                                      title="Severidad {{ $correccion->severidad_label }}">{{ $correccion->severidad === 'alta' ? 'Alta' : ($correccion->severidad === 'media' ? 'Media' : 'Baja') }}</span>
                            </span>
                            <span class="fnc-udash__sub">{{ $correccion->mensaje }}</span>
                        </div>
                    </td>

                    {{-- Valor actual / corregido: truncado con title --}}
                    <td style="min-width: 130px; max-width: 260px;">
                        @if($correccion->estado === 'resuelta')
                            <span class="fnc-udash__sub d-block text-truncate" title="Antes: {{ $correccion->valor_actual ?: '(vacío)' }}">Antes: {{ $correccion->valor_actual ?: '(vacío)' }}</span>
                            <span class="fnc-udash__name text-success d-block text-truncate" title="{{ $correccion->valor_corregido }}">{{ $correccion->valor_corregido ?: '✓' }}</span>
                        @elseif($correccion->estado === 'ignorada')
                            <span class="fnc-udash__sub d-block text-truncate" title="{{ $correccion->valor_actual ?: '(vacío)' }} — {{ $correccion->justificacion }}">
                                {{ $correccion->valor_actual ?: '(vacío)' }} — justificado
                            </span>
                        @else
                            <span class="fnc-udash__sub d-block text-truncate" title="{{ $correccion->valor_actual }}">{{ $correccion->valor_actual ?: '(vacío)' }}</span>
                        @endif
                    </td>

                    {{-- Estado: pill compacta, sin wrap --}}
                    <td class="text-nowrap">
                        <x-fnc.pill :tone="$correccion->estado_tone" dot>{{ ucfirst($correccion->estado) }}</x-fnc.pill>
                    </td>

                    {{-- Acciones: iconos siempre; texto solo en pantallas grandes --}}
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
                                        data-activo="{{ $equipo?->nombre_equipo ?: 'activo' }}"
                                        title="Corregir «{{ $correccion->etiqueta }}»" aria-label="Corregir {{ $correccion->etiqueta }}">
                                    <i class="bi bi-pencil-square"></i><span class="d-none d-xxl-inline ms-1">Corregir</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-ignorar data-id="{{ $correccion->id }}"
                                        data-etiqueta="{{ $correccion->etiqueta }}"
                                        title="Justificar como N/A" aria-label="Justificar {{ $correccion->etiqueta }}">
                                    <i class="bi bi-slash-circle"></i><span class="d-none d-xxl-inline ms-1">Justificar</span>
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
                        Mostrando {{ $correcciones->firstItem() }}–{{ $correcciones->lastItem() }} de {{ $correcciones->total() }} correcciones
                    </small>
                    {{ $correcciones->links() }}
                </div>
            </x-slot>
        @endif
    </x-ui.card>
</div>

{{-- ── Modal de corrección directa ─────────────────────────────────── --}}
<div class="modal fade" id="modalCorregir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('correcciones.resolver', ['correccion' => 'ID', '_tab' => request('_tab')]) }}" id="formCorregir" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Corregir: <span id="corregirEtiqueta"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">
                    Activo: <strong id="corregirActivo"></strong><br>
                    Valor actual: <code id="corregirActual"></code>
                </p>
                <div id="corregirCampoWrapper">
                    <label class="form-label fw-medium" for="corregirValor">Valor real</label>
                    <input type="text" name="valor" id="corregirValor" class="form-control" required>
                </div>
                <div class="form-text text-muted">La corrección se aplica directamente al registro del activo.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Guardar corrección
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal de justificación (N/A con causa) ──────────────────────── --}}
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
                <button type="submit" class="btn btn-secondary">
                    <i class="bi bi-slash-circle me-1"></i> Archivar como justificado
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modalCorregir = null, modalIgnorar = null;

    var TAB = (function () {
        var t = new URLSearchParams(window.location.search).get('_tab');
        return t ? '?_tab=' + encodeURIComponent(t) : '';
    })();

    document.addEventListener('click', function (ev) {
        var btnCorregir = ev.target.closest('[data-corregir]');
        var btnIgnorar   = ev.target.closest('[data-ignorar]');

        if (btnCorregir) {
            var id       = btnCorregir.dataset.id;
            var tipo     = btnCorregir.dataset.tipo || 'texto';
            var catalogo = btnCorregir.dataset.catalogo || '';

            document.getElementById('corregirEtiqueta').textContent = btnCorregir.dataset.etiqueta;
            document.getElementById('corregirActivo').textContent   = btnCorregir.dataset.activo;
            document.getElementById('corregirActual').textContent   = btnCorregir.dataset.actual || '(vacío)';
            document.getElementById('formCorregir').action = '/correcciones/' + id + '/resolver' + TAB;

            var wrapper = document.getElementById('corregirCampoWrapper');
            var input   = document.getElementById('corregirValor');

            if (tipo === 'fecha') {
                wrapper.innerHTML = '<label class="form-label fw-medium" for="corregirValor">Fecha real (dd/mm/aaaa)</label>' +
                    '<input type="date" name="valor" id="corregirValor" class="form-control" required>';
            } else if (tipo === 'catalogo' && catalogo) {
                wrapper.innerHTML = '<label class="form-label fw-medium" for="corregirValor">Valor del catálogo oficial</label>' +
                    '<select name="valor" id="corregirValor" class="form-select" required>' +
                    '<option value="">Cargando opciones…</option></select>';
                fetch('/correcciones/' + id + '/opciones' + TAB, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var sel = document.getElementById('corregirValor');
                        sel.innerHTML = '<option value="">Selecciona…</option>';
                        (data.opciones || []).forEach(function (o) {
                            var opt = document.createElement('option');
                            opt.value = o.valor; opt.textContent = o.etiqueta;
                            sel.appendChild(opt);
                        });
                    })
                    .catch(function () {
                        document.getElementById('corregirValor').innerHTML = '<option value="">No se pudieron cargar las opciones</option>';
                    });
            } else {
                wrapper.innerHTML = '<label class="form-label fw-medium" for="corregirValor">Valor real</label>' +
                    '<input type="text" name="valor" id="corregirValor" class="form-control" required>';
            }

            if (!modalCorregir) modalCorregir = new bootstrap.Modal('#modalCorregir');
            modalCorregir.show();
        }

        if (btnIgnorar) {
            document.getElementById('ignorarEtiqueta').textContent = btnIgnorar.dataset.etiqueta;
            document.getElementById('formIgnorar').action = '/correcciones/' + btnIgnorar.dataset.id + '/ignorar' + TAB;
            if (!modalIgnorar) modalIgnorar = new bootstrap.Modal('#modalIgnorar');
            modalIgnorar.show();
        }
    });
})();
</script>
@endpush
@endsection
