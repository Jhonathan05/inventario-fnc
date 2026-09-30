@extends('layouts.inventario')

@section('title', 'Historial de Asignaciones — ' . $equipo->nombre_equipo)

@section('content')
<x-ui.toolbar 
    title="Historial de Asignaciones" 
    icon="person-fill-gear"
    backRoute="{{ route('equipos.show', $equipo) }}"
>
    <x-slot name="title">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-person-fill-gear me-2 text-primary"></i>Historial de Asignaciones
        </h4>
        <small class="text-muted">{{ $equipo->nombre_equipo }} — Serial: {{ $equipo->serial }}</small>
    </x-slot>
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>#</th>
                <th>Tipo Acción</th>
                <th>Usuario</th>
                <th>Área / Distrito</th>
                <th>Fecha</th>
                <th>Registrado por</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        @forelse($asignaciones as $asignacion)
            <tr>
                <td class="text-muted small">{{ $asignacion->id }}</td>
                <td>
                    <x-ui.badge color="{{ $asignacion->tipo_accion_color }}">
                        {{ $asignacion->tipo_accion_label }}
                    </x-ui.badge>
                </td>
                <td>
                    @if($asignacion->usuario_nombre)
                        <span class="fw-medium">{{ $asignacion->usuario_nombre }}</span>
                        <br><small class="text-muted">CC: {{ $asignacion->usuario_cedula }}</small>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <small>
                        {{ $asignacion->usuario_area ?? '—' }}
                        @if($asignacion->usuario_distrito)
                            <br><span class="text-muted">{{ $asignacion->usuario_distrito }}</span>
                        @endif
                    </small>
                </td>
                <td>
                    <small>{{ $asignacion->fecha_accion?->format('d/m/Y H:i') ?? '—' }}</small>
                </td>
                <td class="small text-muted">{{ $asignacion->registradoPor?->name ?? '—' }}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <x-ui.button href="{{ route('asignaciones.show', $asignacion) }}" outline="true" color="info" title="Ver detalle" icon="eye" />
                        @if(in_array($asignacion->tipo_accion, ['asignacion','reemplazo']))
                        <x-ui.button href="{{ route('asignaciones.pdf', $asignacion) }}" outline="true" color="danger" title="Descargar PDF" icon="file-pdf" />
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay registros de asignaciones para este equipo.
                </td>
            </tr>
        @endforelse
    </x-ui.table>
    @if($asignaciones->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $asignaciones->firstItem() }}–{{ $asignaciones->lastItem() }} de {{ $asignaciones->total() }}
            </small>
            {{ $asignaciones->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-ui.card>
@endsection

