@extends('layouts.inventario')

@section('title', 'Acta #' . $acta->id)

@section('content')
<x-ui.toolbar
    title="Acta #{{ $acta->id }}"
    subtitle="{{ $acta->tipo_accion_label }}"
    icon="clipboard-check"
/>

<div class="row g-3 mb-4">
    <div class="col-12">
        <x-fnc.panel title="Datos del movimiento" icon="arrow-right-left">
            <div class="fnc-acta-campos">
                <div class="fnc-campo"><label>Tipo de novedad</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->tipo_accion_label }}</div></div>
                <div class="fnc-campo"><label>Fecha</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->fecha_accion?->format('d/m/Y H:i') ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Ubicación</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->ubicacion ?? '—' }}</div></div>
                @if ($acta->destino)
                    <div class="fnc-campo"><label>Destino</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ ucfirst($acta->destino) }}</div></div>
                @endif
                @if ($acta->fecha_inicio)
                    <div class="fnc-campo"><label>Fecha inicio</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->fecha_inicio->format('d/m/Y') }}</div></div>
                @endif
                @if ($acta->fecha_fin_probable)
                    <div class="fnc-campo"><label>Fecha fin probable</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->fecha_fin_probable->format('d/m/Y') }}</div></div>
                @endif
                <div class="fnc-campo"><label>Registrada por</label><div class="fnc-input" style="background:transparent;border:none;padding-left:0">{{ $acta->registradoPor?->name ?? '—' }}</div></div>
            </div>
            @if ($acta->observaciones)
                <div class="mt-3"><label class="fnc-label">Observaciones</label><p class="mb-0" style="font-size:.86rem">{{ $acta->observaciones }}</p></div>
            @endif
        </x-fnc.panel>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <x-fnc.panel title="Responsable" icon="user-round">
            <div class="fnc-acta-campos">
                <div class="fnc-campo"><label>Nombre</label><div style="font-size:.86rem;font-weight:600">{{ $acta->usuario_nombre ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Cédula</label><div style="font-size:.86rem">{{ $acta->usuario_cedula ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Cargo</label><div style="font-size:.86rem">{{ $acta->usuario_cargo ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Área</label><div style="font-size:.86rem">{{ $acta->usuario_area ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Dependencia</label><div style="font-size:.86rem">{{ $acta->usuario_dependencia ?? '—' }}</div></div>
                <div class="fnc-campo"><label>Ciudad</label><div style="font-size:.86rem">{{ $acta->usuario_ciudad ?? '—' }}</div></div>
            </div>
        </x-fnc.panel>
    </div>
    <div class="col-lg-6">
        <x-fnc.panel title="Firmas" icon="pen-line">
            <div class="row g-3">
                <div class="col-6">
                    <h6 class="fnc-label mb-2">Quien entrega</h6>
                    <div style="font-size:.84rem;font-weight:600">{{ $acta->entregador_nombre ?? '—' }}</div>
                    <div style="font-size:.74rem;color:var(--fnc-text-3)">{{ $acta->entregador_cargo ?? '' }} {{ $acta->entregador_dependencia ? '· ' . $acta->entregador_dependencia : '' }}</div>
                </div>
                <div class="col-6">
                    <h6 class="fnc-label mb-2">Quien recibe</h6>
                    <div style="font-size:.84rem;font-weight:600">{{ $acta->recibe_nombre ?? '—' }}</div>
                    <div style="font-size:.74rem;color:var(--fnc-text-3)">{{ $acta->recibe_cargo ?? '' }} {{ $acta->recibe_dependencia ? '· ' . $acta->recibe_dependencia : '' }}</div>
                </div>
            </div>
        </x-fnc.panel>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <x-fnc.panel>
            <x-slot:title>
                Activos del acta
                <span class="fnc-items__count ms-2">
                    <x-fnc.icon name="package" :size="12" />
                    {{ $acta->items->count() }}
                </span>
            </x-slot:title>
            <x-slot:icon>monitor</x-slot:icon>

            @if ($acta->items->isNotEmpty())
                <table class="fnc-items">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Activo</th>
                            <th>Tipo</th>
                            <th>Serial</th>
                            <th>Placa</th>
                            <th>Marca / Modelo</th>
                            <th>Estado</th>
                            <th>Uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($acta->items as $item)
                            @php $equipo = $item->equipo; @endphp
                            <tr>
                                <td>
                                    <div class="fnc-items__id">
                                        <span class="fnc-items__icon">
                                            <x-fnc.icon name="monitor" :size="17" />
                                        </span>
                                        <div class="fnc-items__body">
                                            <div class="fnc-items__name">{{ $equipo?->nombre_equipo ?: $equipo?->serial ?: 'N/A' }}</div>
                                            <div class="fnc-items__serial">{{ $equipo?->tipoRecurso?->nombre ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $equipo?->tipoRecurso?->nombre ?? 'N/A' }}</td>
                                <td class="fnc-table__num">{{ $equipo?->serial ?? 'N/A' }}</td>
                                <td class="fnc-table__num">{{ $equipo?->placa ?? 'N/A' }}</td>
                                <td>{{ trim(($equipo?->marca ?? '') . ' ' . ($equipo?->modelo ?? '')) ?: 'N/A' }}</td>
                                <td>
                                    <x-fnc.pill
                                        :tone="\App\Services\Dashboard\DashboardService::estadoTone($equipo?->estado_operativo ?? '')"
                                        dot>
                                        {{ \App\Services\Dashboard\DashboardService::estadoLabel($equipo?->estado_operativo ?? '') }}
                                    </x-fnc.pill>
                                </td>
                                <td>{{ $equipo?->estado_uso ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <x-fnc.empty icon="package" title="Sin ítems" text="Esta acta no tiene activos registrados." />
            @endif
        </x-fnc.panel>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="{{ route('inicio') }}" class="btn btn-secondary">Volver</a>
    @if ($acta->items->isNotEmpty())
        <a href="{{ route('actas.xlsx', $acta) }}" class="btn btn-success" target="_blank">
            <i class="bi bi-file-excel me-1"></i> Excel (novedad)
        </a>
        <a href="{{ route('actas.docx', $acta) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-file-word me-1"></i> Word (responsabilidad)
        </a>
        <a href="{{ route('actas.pdf', $acta) }}" class="btn btn-danger" target="_blank">
            <i class="bi bi-file-pdf me-1"></i> PDF (novedad)
        </a>
        <a href="{{ route('actas.paquete', $acta) }}" class="btn btn-dark" target="_blank">
            <i class="bi bi-file-zip me-1"></i> Paquete completo (ZIP)
        </a>
    @endif
</div>
@endsection
