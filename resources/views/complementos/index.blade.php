@extends('layouts.inventario')

@section('title', 'Complementos del Activo - Administración Global')

@section('content')
<x-ui.toolbar 
    title="Complementos del Activo" 
    icon="box-seam" 
/>


<!-- Filtros -->
<x-ui.filter-container id="formFiltros" action="{{ route('equipos.complementos.global') }}">
    <div class="col-md-4">
        <label class="form-label small">Buscar (Serial, Marca, Equipo)</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" id="buscarInput" class="form-control" value="{{ request('buscar') }}" placeholder="Término de búsqueda...">
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Tipo de Complemento</label>
        <select name="catalogo_id" id="filtroCatalogo" class="form-select">
            <option value="">Todos</option>
            @foreach($catalogo as $cat)
                <option value="{{ $cat->id }}" {{ request('catalogo_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Estado</label>
        <select name="estado" id="filtroEstado" class="form-select">
            <option value="">Todos</option>
            @foreach(\App\Models\ActivoComplemento::ESTADOS as $est)
                <option value="{{ $est }}" {{ request('estado') == $est ? 'selected' : '' }}>{{ $est }}</option>
            @endforeach
        </select>
    </div>
</x-ui.filter-container>

<!-- Tabla de Complementos -->
<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>#</th>
                <th>Tipo / Nombre</th>
                <th>Compatible con</th>
                <th>Marca & Modelo</th>
                <th>Serial</th>
                <th>Estado</th>
                <th>Activo Asociado</th>
                <th>Fecha Reg.</th>
                <th>Acciones</th>
            </tr>
        </x-slot>
        
        @forelse($complementos as $comp)
            <tr>
                <td class="text-muted small text-nowrap">{{ $comp->id }}</td>
                <td class="fw-medium text-nowrap">
                    {{ $comp->nombre }}
                    @if($comp->cantidad > 1)
                        <x-ui.badge color="secondary" class="ms-1">x{{ $comp->cantidad }}</x-ui.badge>
                    @endif
                </td>
                <td>
                    @if($comp->catalogoComplemento && $comp->catalogoComplemento->tipoRecursos->count() > 0)
                        <span class="text-muted small">{{ $comp->catalogoComplemento->tipoRecursos->pluck('nombre')->join(', ') }}</span>
                    @else
                        <span class="text-danger small">No definido</span>
                    @endif
                </td>
                <td class="text-nowrap">
                    {{ $comp->marca ?: 'N/A' }} <br>
                    <span class="text-muted small">{{ $comp->modelo ?: 'N/A' }}</span>
                </td>
                <td class="fw-medium font-monospace small text-nowrap">{{ $comp->serial ?: 'SIN SERIAL' }}</td>
                <td class="text-nowrap">
                    @if(empty($comp->equipo_id) || $comp->estado == 'Disponible')
                        <x-ui.badge color="success rounded-pill">Disponible</x-ui.badge>
                    @elseif($comp->estado == 'Dañado' || $comp->estado == 'Malo')
                        <x-ui.badge color="danger rounded-pill">{{ $comp->estado }}</x-ui.badge>
                    @elseif($comp->estado == 'En reparación')
                        <x-ui.badge color="warning text-dark rounded-pill">{{ $comp->estado }}</x-ui.badge>
                    @else
                        <x-ui.badge color="primary rounded-pill">{{ $comp->estado ?: 'Bueno' }}</x-ui.badge>
                    @endif
                </td>
                <td class="text-nowrap">
                    @if($comp->equipo)
                        <a href="{{ route('equipos.show', $comp->equipo->id) }}" class="text-decoration-none fw-semibold">
                            {{ $comp->equipo->placa ?: 'S/P' }}
                        </a><br>
                        <span class="text-muted small">{{ $comp->equipo->tipoRecurso->nombre ?? '' }}</span>
                    @else
                        <span class="text-muted fst-italic">Sin asignar</span>
                    @endif
                </td>
                <td class="text-muted small text-nowrap">{{ $comp->fecha_registro ? $comp->fecha_registro->format('Y-m-d') : $comp->created_at->format('Y-m-d') }}</td>
                <td class="text-nowrap">
                    <a href="{{ route('equipos.complementos.historial_individual', $comp->id) }}" class="btn btn-sm btn-outline-info" title="Ver Historial">
                        <i class="bi bi-clock-history"></i> Historial
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay complementos registrados.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    @if($complementos->hasPages())
        <div class="card-footer bg-white border-0">
            {{ $complementos->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-ui.card>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-control { min-height: 38px; padding: 6px 12px; }
    .ts-control > input { padding: 0 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar TomSelect
        if (document.getElementById('filtroCatalogo')) {
            new TomSelect('#filtroCatalogo', {
                placeholder: 'Todos',
                searchField: ['text'],
                maxOptions: 50
            });
        }
        
        if (document.getElementById('filtroEstado')) {
            new TomSelect('#filtroEstado', {
                placeholder: 'Todos',
                searchField: ['text']
            });
        }

        // Auto-submit via AJAX
        const formFiltros = document.getElementById('formFiltros');
        const selectCatalogo = document.getElementById('filtroCatalogo');
        const selectEstado = document.getElementById('filtroEstado');
        const searchInput = document.getElementById('buscarInput');
        const tablaContainer = document.querySelector('.table-responsive');
        const footerContainer = document.querySelector('.card-footer') ? document.querySelector('.card-footer').parentElement : document.querySelector('.table-responsive').parentElement;
        
        let searchTimeoutId;

        function updateTable() {
            // Mostrar indicador de carga visualmente
            tablaContainer.style.opacity = '0.5';
            
            // Construir la URL con todos los filtros actuales del formulario
            const formData = new FormData(formFiltros);
            const params = new URLSearchParams(formData);
            const url = `${formFiltros.action}?${params.toString()}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                // Parsear el HTML devuelto
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Extraer el nuevo tbody
                const newTbody = doc.querySelector('.table-responsive tbody');
                const currentTbody = tablaContainer.querySelector('tbody');
                
                if (newTbody && currentTbody) {
                    currentTbody.innerHTML = newTbody.innerHTML;
                }
                
                // Extraer e inyectar la paginación (card-footer)
                const newFooter = doc.querySelector('.card-footer');
                const currentFooter = document.querySelector('.card-footer');
                
                if (newFooter && footerContainer) {
                    if (currentFooter) {
                        currentFooter.outerHTML = newFooter.outerHTML;
                    } else {
                        footerContainer.appendChild(newFooter);
                    }
                } else if (!newFooter && currentFooter) {
                    currentFooter.remove();
                }

                // Restaurar opacidad
                tablaContainer.style.opacity = '1';
                
                // Actualizar la URL del navegador sin recargar (para que al recargar mantenga el filtro)
                window.history.pushState({}, '', url);
            })
            .catch(error => {
                console.error('Error al filtrar:', error);
                tablaContainer.style.opacity = '1';
            });
        }

        if (selectCatalogo) {
            selectCatalogo.addEventListener('change', updateTable);
        }
        if (selectEstado) {
            selectEstado.addEventListener('change', updateTable);
        }
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeoutId);
                searchTimeoutId = setTimeout(updateTable, 500);
            });
        }
    });
</script>
@endpush
