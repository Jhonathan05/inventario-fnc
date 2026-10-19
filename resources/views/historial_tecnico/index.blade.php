@extends('layouts.inventario')

@section('title', 'Gestión de Reparaciones')

@section('content')
<x-ui.toolbar 
    title="Gestión de Reparaciones" 
    icon="clock-history"
>
    <x-slot name="subtitle">Vista global de todas las hojas de vida y soporte técnico ({{ $registros->total() }} registros)</x-slot>
</x-ui.toolbar>

<x-ui.card noPadding="true">
    <div class="p-4 border-bottom border-light">
        <form action="{{ route('historial-tecnico.index') }}" method="GET" class="d-flex flex-column flex-lg-row gap-3 align-items-stretch align-items-lg-center w-100">
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
                <th class="fnc-fhd-only">Registrado por</th>
                <th class="fnc-fhd-only">Adjuntos</th>
                <th class="fnc-fhd-only">Motivo</th>
                <th class="text-end pe-4">Acción</th>
            </tr>
        </x-slot>
        @forelse($registros as $reg)
        @php
            $toneEstadoRep = match ($reg->estado) {
                'Finalizado' => 'success',
                'En proceso' => 'warning',
                'Suspendido' => 'danger',
                default => 'info',
            };
        @endphp
        <tr>
            <td class="ps-4">
                <span class="fnc-udash__names">
                    <span class="fnc-udash__name">{{ $reg->equipo->nombre_equipo ?? 'N/A' }}</span>
                    <span class="fnc-udash__sub">{{ $reg->equipo->serial_visual ?? '' }}</span>
                </span>
            </td>
            <td>
                <span class="d-inline-flex flex-column gap-1 align-items-start">
                    <x-fnc.pill tone="slate">{{ \Str::title(str_replace('_', ' ', $reg->tipo_evento)) }}</x-fnc.pill>
                    <x-fnc.pill :tone="$toneEstadoRep" dot>{{ $reg->estado }}</x-fnc.pill>
                </span>
            </td>
            <td class="text-muted maint-truncate-td">
                {{ $reg->observaciones ?: $reg->descripcion }}
            </td>
            <td>
                <span class="fnc-udash__user">
                    <span class="fnc-udash__avatar" aria-hidden="true">{{ mb_substr($reg->usuario_responsable_label, 0, 1) }}</span>
                    <span class="fnc-udash__unombre">{{ $reg->usuario_responsable_label }}</span>
                </span>
            </td>
            <td class="text-nowrap">
                <span class="fnc-udash__when">
                    <span class="fnc-udash__whenrel">{{ \Carbon\Carbon::parse($reg->fecha_evento)->diffForHumans() }}</span>
                    <span class="fnc-udash__sub">{{ \Carbon\Carbon::parse($reg->fecha_evento)->format('d M Y') }}</span>
                </span>
            </td>
            <td class="fnc-fhd-only text-nowrap">{{ $reg->registradoPor?->name ?? '—' }}</td>
            <td class="fnc-fhd-only text-nowrap">
                @php $adjuntosReg = is_array($reg->archivos) ? count($reg->archivos) : 0; @endphp
                <x-fnc.pill :tone="$adjuntosReg > 0 ? 'info' : 'slate'">{{ $adjuntosReg }}</x-fnc.pill>
            </td>
            <td class="fnc-fhd-only">
                <span class="fnc-udash__sub d-inline-block text-truncate" style="max-width: 220px;" title="{{ $reg->motivo }}">
                    {{ $reg->motivo ?: '—' }}
                </span>
            </td>
            <td class="text-end pe-4 text-nowrap">
                <div class="d-inline-flex align-items-center gap-1">
                    <x-ui.button href="{{ route('historial-tecnico.show', $reg) }}" color="light" class="btn-sm rounded-circle" icon="eye" title="Ver detalle" />
                    @if($reg->equipo_id)
                        <x-ui.button href="{{ route('historial-tecnico.por-equipo', ['equipo' => $reg->equipo_id, 'return_to' => request()->fullUrl()]) }}" color="light" class="btn-sm rounded-circle" icon="clock-history" title="Ver historial del activo" />
                    @endif
                    @can('equipos.crear')
                    @if($reg->equipo && in_array($reg->equipo->estado_operativo, ['reparacion', 'baja'], true))
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
            <td colspan="9" class="text-center py-5 text-muted">
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


