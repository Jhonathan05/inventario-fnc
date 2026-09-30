@extends('layouts.inventario')

@section('title', 'Préstamos')

@section('content')
<x-ui.toolbar 
    title="Historial de Préstamos" 
    icon="person-fill-gear"
/>

{{-- Filtros --}}
<x-ui.filter-container action="{{ route('asignaciones.index') }}" clearRoute="{{ route('asignaciones.index') }}">
    <div class="col-md-5">
        <label class="form-label fw-medium small mb-1">Buscar</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control" placeholder="Nombre, cédula, equipo, serial...">
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-medium small mb-1">Tipo de acción</label>
        <select name="tipo_accion" class="form-select">
            <option value="">Todos</option>
            @foreach($tiposAccion as $key => $label)
                <option value="{{ $key }}" {{ request('tipo_accion') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</x-ui.filter-container>

{{-- Tabla --}}
<x-ui.card noPadding="true">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th>#</th>
                <th>Equipo</th>
                <th>Usuario Asignado</th>
                <th>Tipo Acción</th>
                <th>Fecha</th>
                <th>Registrado por</th>
                <th class="text-center">Acciones</th>
            </tr>
        </x-slot>
        @forelse($asignaciones as $asignacion)
            <tr>
                <td class="text-muted small text-nowrap">{{ $asignacion->id }}</td>
                <td>
                    <span class="fw-medium">{{ $asignacion->equipo?->nombre_equipo ?? '—' }}</span>
                    <br><small class="text-muted font-monospace">{{ $asignacion->equipo?->serial_visual ?? '' }}</small>
                </td>
                <td>
                    @if($asignacion->usuario_nombre)
                        {{ $asignacion->usuario_nombre }}
                        <br><small class="text-muted">CC: {{ $asignacion->usuario_cedula }}</small>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <x-ui.badge color="{{ $asignacion->tipo_accion_color }}">
                        {{ $asignacion->tipo_accion_label }}
                    </x-ui.badge>
                </td>
                <td>
                    <span class="small">{{ $asignacion->fecha_accion?->format('d/m/Y H:i') ?? '—' }}</span>
                </td>
                <td class="small text-muted text-nowrap">{{ $asignacion->registradoPor?->name ?? '—' }}</td>
                <td class="text-center text-nowrap">
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
                    No hay registros de préstamos.
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

