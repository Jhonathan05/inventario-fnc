@extends('layouts.inventario')

@section('title', 'Historial Técnico — ' . $equipo->nombre_equipo)

@section('content')
@php
    $puedeModificarBitacora = in_array((string) $equipo->estado_operativo, ['mantenimiento', 'baja'], true);
@endphp

<x-ui.toolbar 
    title="Historial Técnico" 
    icon="tools"
    iconClass="text-warning"
>
    <x-slot name="subtitle">{{ $equipo->nombre_equipo }} — Serial: {{ $equipo->serial }}</x-slot>
    <x-ui.button href="{{ route('historial-tecnico.create', ['equipo_id' => $equipo->id, 'return_to' => request()->fullUrl()]) }}" color="primary" icon="plus-lg" text="Nuevo Evento" />
    <x-ui.button href="{{ $volverUrl }}" color="secondary" outline="true" icon="arrow-left" text="Volver" />
</x-ui.toolbar>

@if($registros->isEmpty())
    <x-ui.card class="border-0 shadow-sm text-center py-5 text-muted">
        <i class="bi bi-tools fs-2 d-block mb-2"></i>
        No hay eventos técnicos registrados para este equipo.
    </x-ui.card>
@else

{{-- Historial en Tabla --}}
<x-ui.card noPadding="true">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2 text-warning"></i>Lista de Eventos Técnicos</h5>
        <span class="badge bg-warning text-dark">{{ $registros->count() }} eventos</span>
    </div>
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="ps-4">Fecha</th>
                <th>Tipo de Evento</th>
                <th>Detalles</th>
                <th>Involucrados</th>
                <th class="text-end pe-4">Acciones</th>
            </tr>
        </x-slot>
        <tbody>
            @foreach($registros as $registro)
            <tr>
                <td class="ps-4">
                    <span class="fw-medium">{{ $registro->fecha_evento?->format('d/m/Y') }}</span>
                    <br><small class="text-muted">{{ $registro->fecha_evento?->format('h:i A') }}</small>
                </td>
                <td>
                    <span class="badge bg-{{ $registro->tipo_evento_color }}">
                        <i class="bi {{ $registro->tipo_evento_icono }} me-1"></i>
                        {{ $registro->tipo_evento_label }}
                    </span>
                </td>
                <td class="text-wrap" style="min-width: 250px;">
                    <strong>{{ $registro->observaciones ?: $registro->descripcion }}</strong>
                    @if($registro->archivos && count($registro->archivos) > 0)
                    <div class="mt-2">
                        @foreach($registro->archivos as $archivo)
                        <a href="{{ asset('storage/' . $archivo['ruta']) }}" target="_blank" class="badge bg-light text-dark border text-decoration-none me-1">
                            <i class="bi bi-paperclip"></i> {{ strlen($archivo['nombre']) > 15 ? substr($archivo['nombre'], 0, 15).'...' : $archivo['nombre'] }}
                        </a>
                        @endforeach
                    </div>
                    @endif
                </td>
                <td>
                    <div class="small">
                        <i class="bi bi-person text-muted me-1"></i><strong>Resp:</strong> {{ $registro->usuario_responsable_label }}
                    </div>
                    @if($registro->usuario_asignado_snapshot)
                    <div class="small text-muted mt-1">
                        <i class="bi bi-person-check text-success me-1"></i><strong>Asignado:</strong> {{ $registro->usuario_asignado_snapshot['nombre'] ?? '—' }}
                    </div>
                    @endif
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex gap-1 justify-content-end flex-nowrap">
                        <x-ui.button href="{{ route('historial-tecnico.show', $registro) }}" color="light" size="sm" class="rounded-circle" title="Ver" icon="eye" />
                        @if($puedeModificarBitacora)
                            <x-ui.button href="{{ route('historial-tecnico.edit', ['historialTecnico' => $registro->id, 'return_to' => request()->fullUrl()]) }}" color="light" size="sm" class="rounded-circle" title="Editar" icon="pencil" />
                            <x-ui.button type="button" outline="true" color="danger" size="sm" class="rounded-circle" data-delete-url="{{ route('historial-tecnico.destroy', $registro) }}" data-delete-name="el evento del {{ $registro->fecha_evento?->format('d/m/Y') }}" title="Eliminar" icon="trash" />
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </x-ui.table>
</x-ui.card>
@endif
@endsection
