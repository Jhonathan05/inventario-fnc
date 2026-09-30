@extends('layouts.inventario')

@section('title', 'Licencias')

@section('content')
<x-ui.toolbar 
    title="Gestión de Licencias" 
    icon="key" 
>
    @can('licencias.exportar')
        <x-ui.button href="{{ route('licencias.exportar', request()->query()) }}" color="success" icon="file-earmark-excel" text="Exportar Excel" />
    @endcan
    <x-ui.button href="{{ route('licencia-asignaciones.index') }}" outline="true" color="primary" icon="list-check" text="Ver Asignaciones" />
    <x-ui.button href="{{ route('licencias.historial') }}" outline="true" color="secondary" icon="clock-history" text="Historial" />
    @can('licencias.crear')
        <x-ui.button href="{{ route('licencias.create') }}" color="primary" icon="plus-lg" text="Nueva Licencia" />
    @endcan
</x-ui.toolbar>

{{-- Alertas --}}
@if($alertasRojas > 0 && !session('licencias_vencidas_alert_shown_index'))
@php session()->put('licencias_vencidas_alert_shown_index', true); @endphp
<div class="alert alert-danger d-flex align-items-center licencia-alerta" role="alert">
    <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
    <div>
        <strong>¡Atención!</strong> Hay {{ $alertasRojas }} licencia(s) vencida(s).
    </div>
</div>
@endif

@if($alertasAmarillas > 0 && !session('licencias_por_vencer_alert_shown_index'))
@php session()->put('licencias_por_vencer_alert_shown_index', true); @endphp
<div class="alert alert-warning d-flex align-items-center licencia-alerta" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
    <div>
        <strong>Aviso:</strong> Hay {{ $alertasAmarillas }} licencia(s) por vencer en los próximos 30 días.
    </div>
</div>
@endif

{{-- Filtros --}}
<x-ui.filter-container action="{{ route('licencias.index') }}" clearRoute="{{ route('licencias.index') }}">
    <div class="col-12 col-md-6">
        <label class="form-label fw-medium small mb-1">Buscar</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control" placeholder="Nombre, tipo, estado...">
        </div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label fw-medium small mb-1">Estado</label>
        <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="Activa" {{ request('estado') === 'Activa' ? 'selected' : '' }}>Activa</option>
            <option value="Suspendida" {{ request('estado') === 'Suspendida' ? 'selected' : '' }}>Suspendida</option>
            <option value="Vencida" {{ request('estado') === 'Vencida' ? 'selected' : '' }}>Vencida</option>
        </select>
    </div>
</x-ui.filter-container>

{{-- Tabla --}}
<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Cupos</th>
                <th>Fechas</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        @forelse($licencias as $licencia)
            <tr>
                <td class="text-muted small text-nowrap">{{ $licencia->id }}</td>
                <td>
                    <span class="fw-medium">{{ $licencia->nombre }}</span>
                </td>
                <td class="text-nowrap">
                    {{ $licencia->tipo_licencia }}
                </td>
                <td class="text-nowrap">
                    @if($licencia->tipo_licencia === 'Vitalicia')
                        <span class="text-muted">N/A</span>
                    @else
                        <x-ui.badge color="{{ $licencia->cupos_disponibles > 0 ? 'success' : 'danger' }}">
                            {{ $licencia->cupos_asignados }} / {{ $licencia->cantidad_maxima }} Asignados
                        </x-ui.badge>
                    @endif
                </td>
                <td class="text-nowrap">
                    @if($licencia->tipo_licencia === 'Vitalicia')
                        <span class="text-muted">Ilimitado</span>
                    @else
                        <small class="text-muted">Inicio:</small> {{ $licencia->fecha_inicio ? $licencia->fecha_inicio->format('d/m/Y') : 'N/A' }}<br>
                        <small class="text-muted">Vence:</small> <strong class="{{ $licencia->fecha_vencimiento && $licencia->fecha_vencimiento->isPast() ? 'text-danger' : '' }}">{{ $licencia->fecha_vencimiento ? $licencia->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</strong>
                    @endif
                </td>
                <td class="text-nowrap">
                    <x-ui.badge color="{{ $licencia->estado === 'Activa' ? 'success' : ($licencia->estado === 'Suspendida' ? 'warning' : 'danger') }}">
                        {{ $licencia->estado }}
                    </x-ui.badge>
                </td>
                <td class="text-center text-nowrap">
                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                        <x-ui.button href="{{ route('licencias.show', $licencia) }}" outline="true" color="info" size="sm" title="Ver detalle" icon="eye" />
                        @can('licencias.editar')
                            <x-ui.button href="{{ route('licencias.edit', $licencia) }}" outline="true" color="warning" size="sm" title="Editar" icon="pencil" />
                        @endcan
                        @can('licencias.eliminar')
                        <x-ui.button type="button" color="danger" outline="true" size="sm" title="Eliminar" icon="trash" data-delete-url="{{ route('licencias.destroy', $licencia) }}" data-delete-name="{{ $licencia->nombre }}" />
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay licencias registradas.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    @if($licencias->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $licencias->firstItem() }}–{{ $licencias->lastItem() }} de {{ $licencias->total() }} licencias
            </small>
            {{ $licencias->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-ui.card>
@endsection

@push('scripts')
<script>
    setTimeout(function() {
        const alertas = document.querySelectorAll('.licencia-alerta');
        alertas.forEach(alerta => {
            alerta.style.transition = "opacity 0.5s ease";
            alerta.style.opacity = "0";
            setTimeout(() => alerta.remove(), 500);
        });
    }, 10000);
</script>
@endpush

