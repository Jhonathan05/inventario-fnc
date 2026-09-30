@extends('layouts.inventario')

@section('title', 'Mesa de Ayuda')

@section('content')
<x-ui.toolbar 
    title="Mesa de Ayuda TIC" 
    subtitle="Centro de gestión de incidentes y servicios tecnológicos"
>
    @can('mesaayuda.crear')
    <x-ui.button href="{{ route('tickets.create') }}" color="primary" icon="plus-lg" text="Nuevo Ticket" />
    @endcan
</x-ui.toolbar>

<!-- Removed Dashboard Metrics Row as requested -->

<x-ui.card noPadding="true" class="border-0 shadow-sm">
    <div class="p-4 border-bottom border-light">
        <form action="{{ route('tickets.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-3 align-items-stretch align-items-md-center w-100">
            <div class="search-bar flex-grow-1 ticket-search-bar w-100 bg-light rounded px-3 py-2 border">
                <i class="bi bi-search text-muted me-2"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Busca por título, funcionario o ID..." class="border-0 bg-transparent outline-none" style="outline: none; width: 90%;">
            </div>
            
            <div class="ticket-status-filter w-100">
                <select name="estado" class="form-select bg-light border" onchange="this.form.submit()">
                    <option value="">Cualquier Estado</option>
                    <option value="Abierto" {{ request('estado') == 'Abierto' ? 'selected' : '' }}>Abierto</option>
                    <option value="En Diagnóstico" {{ request('estado') == 'En Diagnóstico' ? 'selected' : '' }}>En Diagnóstico</option>
                    <option value="En Proceso" {{ request('estado') == 'En Proceso' ? 'selected' : '' }}>En Proceso</option>
                    <option value="Pendiente" {{ request('estado') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="Resuelto" {{ request('estado') == 'Resuelto' ? 'selected' : '' }}>Resuelto</option>
                    <option value="Cerrado" {{ request('estado') == 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                </select>
            </div>
        </form>
    </div>
    
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="ps-4">Ticket</th>
                <th>Solicitante</th>
                <th>Estado / Prioridad</th>
                <th>Responsable</th>
                <th>Tiempo Abierto</th>
                <th class="text-end pe-4">Acción</th>
            </tr>
        </x-slot>
        @forelse($tickets as $ticket)
        <tr>
            <td class="ps-4 text-nowrap">
                <span class="fw-bold text-dark">#{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}</span><br>
                <small class="text-muted fw-medium">{{ Str::limit($ticket->titulo, 50) }}</small>
            </td>
            <td>
                {{ $ticket->funcionario->nombres ?? 'Desconocido' }}<br>
                <small class="text-muted">{{ $ticket->funcionario->cargo ?? '' }}</small>
            </td>
            <td class="text-nowrap">
                <span class="badge {{ 
                    in_array($ticket->estado, ['Abierto', 'En Diagnóstico']) ? 'bg-primary' : 
                    (in_array($ticket->estado, ['Resuelto', 'Cerrado']) ? 'bg-secondary' : 'bg-warning text-dark') 
                }} mb-1">{{ $ticket->estado }}</span><br>
                
                @php
                    $badgePrioridad = 'bg-success'; // Baja
                    if($ticket->prioridad == 'Media') $badgePrioridad = 'bg-warning text-dark';
                    if($ticket->prioridad == 'Alta') $badgePrioridad = 'bg-orange'; // Asumiendo estilo o usar bg-danger bg-opacity-75
                    if($ticket->prioridad == 'Crítica') $badgePrioridad = 'bg-danger';
                @endphp
                <span class="badge {{ $badgePrioridad }}">{{ $ticket->prioridad }}</span>
            </td>
            <td class="text-muted text-nowrap">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle fs-5"></i>
                    {{ $ticket->responsable->name ?? 'Sin asignar' }}
                </div>
            </td>
            <td class="text-nowrap">
                @if(in_array($ticket->estado, ['Resuelto', 'Cerrado']) && $ticket->fecha_cierre)
                    <span class="text-success small fw-bold">
                        Cerrado en {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans($ticket->fecha_cierre, true) }}
                    </span>
                @else
                    <span class="text-muted small">
                        Hace {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans(null, true) }}
                    </span>
                @endif
                <br>
                <small class="text-muted" style="font-size: 0.7rem;">Solicitado: {{ $ticket->fecha_solicitud ? $ticket->fecha_solicitud->format('d/m/Y') : $ticket->created_at->format('d/m/Y') }}</small>
            </td>
            <td class="text-end pe-4 text-nowrap">
                <x-ui.button href="{{ route('tickets.show', $ticket) }}" color="primary" outline="true" size="sm" class="rounded-pill px-3" text="Ver Detalle" />
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <div class="my-4 text-uppercase fw-semibold ticket-empty-state">
                    NO SE ENCONTRARON TICKETS REGISTRADOS
                </div>
            </td>
        </tr>
        @endforelse
    </x-ui.table>
    @if($tickets->hasPages())
    <div class="p-3 border-top bg-light">
        {{ $tickets->links() }}
    </div>
    @endif
</x-ui.card>
@endsection
