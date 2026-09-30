@extends('layouts.inventario')

@section('title', 'Gestión de Mantenimientos')

@section('content')
<x-ui.toolbar 
    title="Gestión de Mantenimientos" 
    icon="clock-history"
>
    <x-slot name="subtitle">Vista global de todas las hojas de vida y soporte técnico ({{ $registros->total() }} registros)</x-slot>
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <div class="p-4 border-bottom border-light">
        <form action="{{ route('historial-tecnico.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-3 align-items-stretch align-items-md-center w-100">
            <div class="search-bar flex-grow-1 maint-search-bar w-100 position-relative">
                <i class="bi bi-search text-muted position-absolute top-50 translate-middle-y ms-3"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Busca por placa, descripción o caso..." class="form-control ps-5 w-100 border rounded-pill">
            </div>
            <x-ui.button type="button" outline="true" color="secondary" class="rounded-pill px-4 text-nowrap" icon="funnel" text="Más Filtros" />
        </form>
    </div>
    
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="ps-4">Activo</th>
                <th>Tipo / Estado</th>
                <th>Bitácora</th>
                <th>Responsable</th>
                <th>Fecha</th>
                <th class="text-end pe-4">Acción</th>
            </tr>
        </x-slot>
        @forelse($registros as $reg)
        <tr>
            <td class="ps-4 fw-medium">
                {{ $reg->equipo->nombre_equipo ?? 'N/A' }}
                <br><small class="text-muted">{{ $reg->equipo->serial_visual ?? '' }}</small>
            </td>
            <td>
                <span class="badge bg-light text-dark border">{{ \Str::title(str_replace('_', ' ', $reg->tipo_evento)) }}</span><br>
                <span class="badge mt-1 {{ 
                    $reg->estado == 'Finalizado' ? 'badge-success' : 
                    ($reg->estado == 'En proceso' ? 'badge-warning' : 
                    ($reg->estado == 'Suspendido' ? 'badge-danger' : 'badge-info')) 
                }}">{{ $reg->estado }}</span>
            </td>
            <td class="text-muted maint-truncate-td">
                {{ $reg->observaciones ?: $reg->descripcion }}
            </td>
            <td>{{ $reg->usuario_responsable_label }}</td>
            <td class="text-nowrap">{{ \Carbon\Carbon::parse($reg->fecha_evento)->format('d M Y') }}</td>
            <td class="text-end pe-4 text-nowrap">
                <div class="d-inline-flex align-items-center gap-1">
                    <x-ui.button href="{{ route('historial-tecnico.show', $reg) }}" color="light" class="btn-sm rounded-circle" icon="eye" title="Ver detalle" />
                    @if($reg->equipo_id)
                        <x-ui.button href="{{ route('historial-tecnico.por-equipo', ['equipo' => $reg->equipo_id, 'return_to' => request()->fullUrl()]) }}" color="light" class="btn-sm rounded-circle" icon="clock-history" title="Ver historial del activo" />
                    @endif
                    @can('equipos.crear')
                    @if($reg->equipo && in_array($reg->equipo->estado_operativo, ['mantenimiento', 'baja'], true))
                        <form method="POST"
                              action="{{ route('asignaciones.store') }}"
                                                                    class="d-inline js-form-restaurar"
                                                                    data-equipo="{{ $reg->equipo->nombre_equipo ?? 'este equipo' }}">
                            @csrf
                            <input type="hidden" name="equipo_id" value="{{ $reg->equipo_id }}">
                            <input type="hidden" name="tipo_accion" value="restauracion">
                                                                <input type="hidden" name="return_to" value="{{ route('historial-tecnico.por-equipo', ['equipo' => $reg->equipo_id, 'return_to' => request()->fullUrl()]) }}">
                            <x-ui.button type="submit" outline="true" color="success" class="btn-sm rounded-circle" icon="arrow-repeat" title="Restaurar equipo a Activo" />
                        </form>
                    @endif
                    @endcan
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                NO SE ENCONTRARON TICKETS REGISTRADOS
            </td>
        </tr>
        @endforelse
    </x-ui.table>
    @if($registros->hasPages())
    <div class="p-3 border-top">
        {{ $registros->links() }}
    </div>
    @endif
</x-ui.card>
@endsection


