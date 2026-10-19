@extends('layouts.inventario')

@section('title', 'Historial Administrativo')

@section('content')
<x-ui.toolbar
    title="Historial Administrativo"
    icon="clock-history"
>
    <x-slot name="subtitle">Registro global de cambios administrativos de los activos ({{ $registros->total() }} registros)</x-slot>
</x-ui.toolbar>

<x-ui.filter-container action="{{ route('historial-administrativo.index') }}" clearRoute="{{ route('historial-administrativo.index') }}">
    <div class="col-12 col-lg-6">
        <label class="form-label fw-medium small mb-1">Buscar</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   class="form-control" placeholder="Descripción o nombre del activo...">
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <label class="form-label fw-medium small mb-1">Tipo de cambio</label>
        <select name="tipo_cambio" class="form-select">
            <option value="">Todos</option>
            @foreach($tiposCambio as $clave => $etiqueta)
                <option value="{{ $clave }}" {{ request('tipo_cambio') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
</x-ui.filter-container>

<x-ui.card noPadding="true">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Activo</th>
                    <th>Tipo de cambio</th>
                    <th>Detalle</th>
                    <th>Realizado por</th>
                    <th class="text-end pe-4">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $reg)
                    <tr>
                        <td class="ps-4">
                            @if($reg->equipo)
                                <span class="fnc-udash__names">
                                    <a class="fnc-udash__name text-decoration-none" href="{{ route('equipos.show', $reg->equipo) }}">
                                        {{ $reg->equipo->nombre_equipo ?: $reg->equipo->serial }}
                                    </a>
                                    <span class="fnc-udash__sub">{{ $reg->equipo->serial_visual ?? '' }} · {{ $reg->equipo->placa_visual ?? '' }}</span>
                                </span>
                            @else
                                <span class="fnc-udash__sub">Activo eliminado</span>
                            @endif
                        </td>
                        <td>
                            <x-fnc.pill tone="slate">{{ $reg->tipo_cambio_label }}</x-fnc.pill>
                        </td>
                        <td class="small text-muted" style="max-width: 380px;">
                            {{ $reg->descripcion }}
                        </td>
                        <td>
                            <span class="fnc-udash__user">
                                <span class="fnc-udash__avatar" aria-hidden="true">{{ $reg->realizadoPor ? mb_substr($reg->realizadoPor->name, 0, 1) : '·' }}</span>
                                <span class="fnc-udash__unombre">{{ $reg->realizadoPor?->name ?? '—' }}</span>
                            </span>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            <span class="fnc-udash__when">
                                <span class="fnc-udash__whenrel">{{ $reg->created_at?->diffForHumans() ?? '—' }}</span>
                                <span class="fnc-udash__sub">{{ $reg->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-50"></i>
                            No hay registros que coincidan con los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<div class="mt-4">
    {{ $registros->links() }}
</div>
@endsection
