@extends('layouts.inventario')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { min-height: 38px; padding: 6px 12px; }
    .ts-control > input { padding: 0 !important; }

    /* Skeleton loader para la tabla */
    .func-row-skeleton td { padding: 10px 16px; }
    .func-skeleton-line {
        height: 14px;
        border-radius: 6px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.2s infinite;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #tablaFuncionariosBody tr { transition: background-color 0.15s ease; }
</style>
@endpush

@section('title', 'Gestión de Funcionarios')

@section('content')
<x-ui.toolbar 
    title="Gestión de Funcionarios" 
    createRoute="{{ route('funcionarios.create') }}" 
    createText="Nuevo Funcionario" 
/>

{{-- Filtros --}}
<x-ui.filter-container action="{{ route('funcionarios.index') }}" clearRoute="{{ route('funcionarios.index') }}" id="formFiltrosFuncionarios">
    <div class="col-12 col-md-5">
        <label class="form-label fw-medium small mb-1">Buscar</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" enterkeyhint="search" name="buscar" id="buscadorFuncionarios" value="{{ request('buscar') }}"
                   class="form-control" placeholder="Nombre, cédula, cargo, área, distrito..." autocomplete="off">
        </div>
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label fw-medium small mb-1">Estado</label>
        <select name="estado" id="filtroEstadoFunc" class="form-select">
            <option value="">Todos</option>
            <option value="Activo" {{ request('estado') === 'Activo' ? 'selected' : '' }}>Activo</option>
            <option value="Inactivo" {{ request('estado') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label fw-medium small mb-1">Área / Gestión</label>
        <select name="area" id="filtroAreaFunc" class="form-select">
            <option value="">Todas las áreas</option>
            @foreach($areasDisponibles as $area)
                <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>{{ $area }}</option>
            @endforeach
        </select>
    </div>
</x-ui.filter-container>

{{-- Contador dinámico --}}
<div class="d-flex align-items-center gap-2 mb-2 px-1">
    <small class="text-muted" id="contadorFuncionarios">
        Mostrando <strong id="contadorNum">{{ $funcionarios->total() }}</strong> funcionario(s)
    </small>
    <div id="spinnerBusqueda" class="spinner-border spinner-border-sm text-primary d-none" role="status" style="width:14px;height:14px;"></div>
</div>

{{-- Tabla --}}
<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="ps-4">Funcionario</th>
                <th>Identificación</th>
                <th>Gestión / Área</th>
                <th>Vinculación</th>
                <th>Activos</th>
                <th>Autorizaciones</th>
                <th class="text-end pe-4">Acción</th>
            </tr>
        </x-slot>

        <tbody id="tablaFuncionariosBody">
        @forelse($funcionarios as $func)
            <tr>
                <td class="ps-4">
                    <span class="fw-medium">{{ $func->nombres }} {{ $func->apellidos }}</span>
                    @if($func->distrito)
                        <br><small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $func->distrito }}</small>
                    @endif
                </td>
                <td class="text-muted text-nowrap">{{ $func->identificacion }}</td>
                <td>
                    <div>{{ $func->cargo ?? '—' }}</div>
                    <small class="text-muted">{{ $func->area ?? '—' }}</small>
                </td>
                <td class="text-nowrap">
                    <x-ui.badge color="{{ $func->estado == 'Activo' ? 'success' : 'secondary' }}">
                        {{ $func->estado }}
                    </x-ui.badge>
                    @if($func->tipo_vinculacion)
                        <br><small class="text-muted mt-1 d-block">{{ $func->tipo_vinculacion }}</small>
                    @endif
                </td>
                <td class="text-nowrap">
                    <x-ui.badge color="{{ $func->equipos_asignados_count > 0 ? 'primary' : 'light text-dark border' }}">
                        <i class="bi bi-laptop me-1"></i> {{ $func->equipos_asignados_count }}
                    </x-ui.badge>
                </td>
                <td class="text-nowrap">
                    <x-ui.badge color="{{ $func->autorizaciones_disponibles_count > 0 ? 'success' : 'light text-dark border' }}" title="Disponibles para asignar">
                        <i class="bi bi-file-earmark-check me-1"></i> {{ $func->autorizaciones_disponibles_count }}
                    </x-ui.badge>
                    <x-ui.badge color="light text-dark border" title="Total históricas">
                        T: {{ $func->autorizaciones_total_count }}
                    </x-ui.badge>
                </td>
                <td class="text-end pe-4 text-nowrap">
                    <div class="d-flex gap-1 justify-content-end flex-nowrap">
                        <x-ui.button href="{{ route('funcionarios.show', $func) }}" color="light" size="sm" class="rounded-circle" title="Ver funcionario" icon="eye" />
                        <x-ui.button href="{{ route('funcionarios.edit', $func) }}" color="light" size="sm" class="rounded-circle" title="Editar funcionario" icon="pencil" />
                        <x-ui.button type="button" outline="true" color="primary" size="sm" class="rounded-circle btn-subir-autorizacion"
                                data-funcionario-id="{{ $func->id }}"
                                data-funcionario-nombre="{{ $func->nombres }} {{ $func->apellidos }}"
                                data-funcionario-cedula="{{ $func->identificacion }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Subir autorización de {{ trim($func->nombres . ' ' . $func->apellidos) }}"
                                icon="upload" />
                    </div>
                </td>
            </tr>
        @empty
            <tr id="rowSinResultados">
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No se encontraron funcionarios con los filtros aplicados.
                </td>
            </tr>
        @endforelse
        </tbody>
    </x-ui.table>
    <div id="paginadorFuncionarios">
        @if($funcionarios->hasPages())
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $funcionarios->firstItem() }}–{{ $funcionarios->lastItem() }} de {{ $funcionarios->total() }} funcionarios
                </small>
                {{ $funcionarios->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</x-ui.card>

{{-- Modal autorización --}}
<x-ui.modal id="modalAutorizacionFuncionario" title="Subir autorización de activo adicional" size="modal-dialog-scrollable modal-fullscreen-md-down">
    <form id="formAutorizacionFuncionario" method="POST" enctype="multipart/form-data">
        @csrf
        <p class="small text-muted mb-3">
            Funcionario: <strong id="autorizacionFuncionarioNombre"></strong><br>
            Cédula: <strong id="autorizacionFuncionarioCedula"></strong>
        </p>
        <div class="mb-3">
            <label class="form-label fw-medium">Archivo firmado <span class="text-danger">*</span></label>
            <input type="file" name="archivo_autorizacion" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <small class="text-muted">Formatos permitidos: PDF, JPG, PNG, WEBP. Máx. 10 MB.</small>
        </div>
        <div>
            <label class="form-label fw-medium">Observaciones</label>
            <textarea name="observaciones" rows="2" class="form-control" maxlength="1000"></textarea>
        </div>
        <x-slot name="footer">
            <x-ui.button type="button" outline="true" color="secondary" data-bs-dismiss="modal" text="Cancelar" />
            <x-ui.button type="submit" color="primary" icon="save" text="Guardar autorización" form="formAutorizacionFuncionario" />
        </x-slot>
    </form>
</x-ui.modal>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─── Tom Select para "Área / Gestión" ────────────────────────────────────
    const areaSelect = document.getElementById('filtroAreaFunc');
    if (areaSelect) {
        new TomSelect(areaSelect, {
            allowEmptyOption: true,
            placeholder: 'Todas las áreas...',
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            maxOptions: 200,
            render: {
                no_results: function () {
                    return '<div class="no-results">No se encontraron áreas</div>';
                }
            }
        });
    }

    // ─── Live Search ─────────────────────────────────────────────────────────
    const buscador    = document.getElementById('buscadorFuncionarios');
    const estadoSel   = document.getElementById('filtroEstadoFunc');
    const spinner     = document.getElementById('spinnerBusqueda');
    const contador    = document.getElementById('contadorNum');
    const tbody       = document.getElementById('tablaFuncionariosBody');
    const paginador   = document.getElementById('paginadorFuncionarios');

    let debounceTimer = null;

    function buildSkeletonRows(n = 5) {
        let html = '';
        for (let i = 0; i < n; i++) {
            html += `<tr class="func-row-skeleton">
                <td><div class="func-skeleton-line" style="width:70%"></div></td>
                <td><div class="func-skeleton-line" style="width:55%"></div></td>
                <td><div class="func-skeleton-line" style="width:65%"></div></td>
                <td><div class="func-skeleton-line" style="width:40%"></div></td>
                <td><div class="func-skeleton-line" style="width:30%"></div></td>
                <td><div class="func-skeleton-line" style="width:30%"></div></td>
                <td><div class="func-skeleton-line" style="width:50%; margin-left:auto"></div></td>
            </tr>`;
        }
        return html;
    }

    function getAreaValue() {
        // Tom Select stores value in the original select
        return areaSelect ? areaSelect.value : '';
    }

    function fetchFuncionarios(buscar, estado, area) {
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(function () {
            spinner.classList.remove('d-none');

            // Show skeleton
            tbody.innerHTML = buildSkeletonRows(5);
            paginador.innerHTML = '';

            const params = new URLSearchParams();
            if (buscar)  params.set('buscar', buscar);
            if (estado)  params.set('estado', estado);
            if (area)    params.set('area', area);
            params.set('_live', '1'); // señal para el servidor (opcional)

            fetch(`{{ route('funcionarios.index') }}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(r => r.text())
            .then(html => {
                const parser  = new DOMParser();
                const doc     = parser.parseFromString(html, 'text/html');
                const newBody = doc.getElementById('tablaFuncionariosBody');
                const newPag  = doc.getElementById('paginadorFuncionarios');
                const newCount = doc.getElementById('contadorNum');

                if (newBody)  tbody.innerHTML  = newBody.innerHTML;
                if (newPag)   paginador.innerHTML = newPag.innerHTML;
                if (newCount && contador) contador.textContent = newCount.textContent;

                // Re-bind tooltip & autorización buttons
                bindTooltips();
                bindAutorizacionButtons();

                spinner.classList.add('d-none');

                // Ya no actualizamos la URL en el navegador porque el usuario solicitó ocultar las rutas
                // const newUrl = `{{ route('funcionarios.index') }}${params.toString() ? '?' + params.toString() : ''}`;
                // history.replaceState(null, '', newUrl.replace('&_live=1', '').replace('?_live=1', ''));
            })
            .catch(() => {
                spinner.classList.add('d-none');
            });
        }, 300); // 300 ms debounce
    }

    // Trigger on input (live)
    if (buscador) {
        buscador.addEventListener('input', function () {
            fetchFuncionarios(this.value.trim(), estadoSel ? estadoSel.value : '', getAreaValue());
        });
        
        // Ocultar el teclado móvil al presionar Enter (Buscar)
        buscador.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscador.blur();
            }
        });
    }

    if (estadoSel) {
        estadoSel.addEventListener('change', function () {
            fetchFuncionarios(buscador ? buscador.value.trim() : '', this.value, getAreaValue());
        });
    }

    // Tom Select: on change trigger live search
    if (areaSelect) {
        // The TomSelect instance fires a 'change' event on the original select
        areaSelect.addEventListener('change', function () {
            fetchFuncionarios(buscador ? buscador.value.trim() : '', estadoSel ? estadoSel.value : '', this.value);
        });
    }

    // ─── Tooltip & Autorización (re-binding) ─────────────────────────────────
    function bindTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            // Dispose old and create new
            const existing = bootstrap.Tooltip.getInstance(el);
            if (existing) existing.dispose();
            new bootstrap.Tooltip(el);
        });
    }

    function bindAutorizacionButtons() {
        const modalEl  = document.getElementById('modalAutorizacionFuncionario');
        const modal    = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        const form     = document.getElementById('formAutorizacionFuncionario');
        const nombreEl = document.getElementById('autorizacionFuncionarioNombre');
        const cedulaEl = document.getElementById('autorizacionFuncionarioCedula');

        document.querySelectorAll('.btn-subir-autorizacion').forEach(btn => {
            // Clone to remove old listeners
            const clone = btn.cloneNode(true);
            btn.parentNode.replaceChild(clone, btn);

            clone.addEventListener('click', function () {
                const id     = this.getAttribute('data-funcionario-id');
                const nombre = this.getAttribute('data-funcionario-nombre') || '';
                const cedula = this.getAttribute('data-funcionario-cedula') || '';
                if (!form) return;
                form.action = `/funcionarios/${id}/autorizaciones`;
                form.reset();
                if (nombreEl) nombreEl.textContent = nombre.trim();
                if (cedulaEl) cedulaEl.textContent = cedula;
                if (modal) modal.show();
            });
        });
    }

    // Initial binding
    bindTooltips();
    bindAutorizacionButtons();
});
</script>
@endpush
@endsection

