@extends('layouts.inventario')

@section('title', 'Historial de Vida — ' . $equipo->nombre_equipo)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2 text-primary"></i>
            Historial de Vida
        </h4>
        <small class="text-muted">{{ $equipo->nombre_equipo }} — Serial: {{ $equipo->serial }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('equipos.show', $equipo) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al equipo
        </a>
    </div>
</div>



{{-- Historial en Tabla --}}
<x-ui.card noPadding="true">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>Línea de Tiempo Completa</h5>
        <span class="badge bg-secondary">{{ $eventos->count() }} eventos</span>
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
            @foreach($eventos as $index => $evento)
            @php
                $fecha = is_string($evento['fecha']) ? \Carbon\Carbon::parse($evento['fecha']) : $evento['fecha'];
            @endphp
            <tr>
                <td class="ps-4">
                    <span class="fw-medium">{{ $fecha->format('d/m/Y') }}</span>
                    @if($evento['tipo'] === 'administrativo' || $evento['tipo'] === 'asignacion')
                        <br><small class="text-muted">{{ $fecha->format('h:i A') }}</small>
                    @endif
                </td>
                <td>
                    @if($evento['tipo'] === 'administrativo' && $evento['subtipo'] === 'cambio_responsable')
                        <span class="badge bg-info text-dark">
                            <i class="bi bi-person-badge me-1"></i>{{ $evento['titulo'] }}
                        </span>
                    @else
                        <span class="badge bg-{{ $evento['color'] }}">
                            <i class="bi {{ $evento['icono'] }} me-1"></i>{{ ucfirst($evento['tipo']) }}
                        </span>
                    @endif
                </td>
                <td class="text-wrap" style="min-width: 250px;">
                    @if(!($evento['tipo'] === 'administrativo' && $evento['subtipo'] === 'cambio_responsable'))
                        <strong>{{ $evento['titulo'] }}</strong>
                    @endif
                    
                    @if($evento['descripcion'])
                        <div class="text-secondary small mt-1">{{ $evento['descripcion'] }}</div>
                    @endif

                    @if($evento['tipo'] === 'asignacion' && $evento['modelo']->motivo)
                        <div class="mt-1 small text-muted">
                            <i class="bi bi-chat-left-text me-1"></i>{{ $evento['modelo']->motivo }}
                        </div>
                    @endif
                </td>
                <td>
                    @if($evento['responsable'])
                    <div class="small">
                        <i class="bi bi-person text-muted me-1"></i>{{ $evento['responsable'] }}
                    </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
                <td class="text-end pe-4">
                    @if($evento['tipo'] === 'asignacion')
                        <x-ui.button href="{{ route('asignaciones.show', $evento['modelo']->id) }}" color="light" size="sm" class="rounded-circle" title="Ver detalle" icon="eye" />
                    @elseif($evento['tipo'] === 'tecnico')
                        <x-ui.button href="{{ route('historial-tecnico.show', $evento['modelo']->id) }}" color="light" size="sm" class="rounded-circle" title="Ver detalle" icon="eye" />
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </x-ui.table>
</x-ui.card>
@endsection

