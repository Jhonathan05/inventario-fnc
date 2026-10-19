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
        <form action="{{ route('tickets.index') }}" method="GET" class="d-flex flex-column flex-lg-row gap-3 align-items-stretch align-items-lg-center w-100">
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
        @php
            $toneTicket = in_array($ticket->estado, ['Abierto', 'En Diagnóstico']) ? 'info'
                : (in_array($ticket->estado, ['Resuelto', 'Cerrado']) ? 'slate' : 'warning');
            $tonePrioridad = match ($ticket->prioridad) {
                'Crítica' => 'danger',
                'Alta' => 'danger',
                'Media' => 'warning',
                'Baja' => 'success',
                default => 'slate',
            };
            $nombreSolicitante = trim(($ticket->funcionario->nombres ?? '') . ' ' . ($ticket->funcionario->apellidos ?? ''));
            $inicialesSol = $nombreSolicitante !== ''
                ? collect(explode(' ', $nombreSolicitante))->reject(fn ($p) => mb_strlen($p) < 2)->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('')
                : '';
        @endphp
        <tr>
            <td class="ps-4 text-nowrap">
                <span class="fnc-udash__when">
                    <span class="fnc-udash__whenrel">#{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <span class="fnc-udash__sub">{{ Str::limit($ticket->titulo, 50) }}</span>
                </span>
            </td>
            <td>
                <span class="fnc-udash__user">
                    <span class="fnc-udash__avatar" aria-hidden="true">{{ $inicialesSol ?: '·' }}</span>
                    <span class="fnc-udash__uname">
                        <span class="fnc-udash__unombre">{{ $nombreSolicitante !== '' ? $nombreSolicitante : 'Desconocido' }}</span>
                        <span class="fnc-udash__sub">{{ $ticket->funcionario->cargo ?? '' }}</span>
                    </span>
                </span>
            </td>
            <td class="text-nowrap">
                <span class="d-inline-flex flex-column gap-1 align-items-start">
                    <x-fnc.pill :tone="$toneTicket" dot>{{ $ticket->estado }}</x-fnc.pill>
                    <x-fnc.pill :tone="$tonePrioridad">{{ $ticket->prioridad }}</x-fnc.pill>
                </span>
            </td>
            <td class="text-nowrap">
                <span class="fnc-udash__user">
                    <span class="fnc-udash__avatar" aria-hidden="true">{{ $ticket->responsable ? mb_substr($ticket->responsable->name, 0, 1) : '—' }}</span>
                    <span class="fnc-udash__unombre">{{ $ticket->responsable->name ?? 'Sin asignar' }}</span>
                </span>
            </td>
            <td class="text-nowrap">
                <span class="fnc-udash__when">
                    @if(in_array($ticket->estado, ['Resuelto', 'Cerrado']) && $ticket->fecha_cierre)
                        <span class="fnc-udash__whenrel text-success">Cerrado en {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans($ticket->fecha_cierre, true) }}</span>
                    @else
                        <span class="fnc-udash__whenrel">Hace {{ Carbon\Carbon::parse($ticket->created_at)->diffForHumans(null, true) }}</span>
                    @endif
                    <span class="fnc-udash__sub">Solicitado: {{ $ticket->fecha_solicitud ? $ticket->fecha_solicitud->format('d/m/Y') : $ticket->created_at->format('d/m/Y') }}</span>
                </span>
            </td>
            <td class="text-end pe-4 text-nowrap">
                <a href="{{ route('tickets.show', $ticket) }}" class="fnc-udash__ver" title="Ver Detalle" aria-label="Ver detalle del ticket #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}">
                    <x-fnc.icon name="chevron-right" :size="15" />
                </a>
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
