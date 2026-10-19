@extends('layouts.inventario')

@section('title', 'Reportes de Licencias')

@section('content')
<x-ui.toolbar
    title="Reportes de Licencias"
    icon="graph-up-arrow"
>
    @can('licencias.exportar')
        <x-ui.button href="{{ route('licencias.exportar') }}" color="success" icon="file-earmark-excel" text="Exportar Excel" />
    @endcan
    <x-ui.button href="{{ route('licencias.index') }}" outline="true" color="primary" icon="list" text="Volver al listado" />
</x-ui.toolbar>

@php $totalEstandar = (int) ($porEstado['Activa'] ?? 0) + (int) ($porEstado['Suspendida'] ?? 0) + (int) ($porEstado['Vencida'] ?? 0); @endphp

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Licencias activas</div>
                        <div class="h4 mb-0 fw-bold text-primary">{{ (int) ($porEstado['Activa'] ?? 0) }}</div>
                    </div>
                    <div class="align-self-center"><i class="bi bi-key-fill fa-2x text-primary opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Suspendidas</div>
                        <div class="h4 mb-0 fw-bold text-warning">{{ (int) ($porEstado['Suspendida'] ?? 0) }}</div>
                    </div>
                    <div class="align-self-center"><i class="bi bi-pause-fill fa-2x text-warning opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Vencidas</div>
                        <div class="h4 mb-0 fw-bold text-danger">{{ (int) ($porEstado['Vencida'] ?? 0) }}</div>
                    </div>
                    <div class="align-self-center"><i class="bi bi-x-octagon-fill fa-2x text-danger opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-secondary border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Total registradas</div>
                        <div class="h4 mb-0 fw-bold">{{ $totalEstandar }}</div>
                    </div>
                    <div class="align-self-center"><i class="bi bi-collection-fill fa-2x text-secondary opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Por tipo --}}
    <div class="col-12 col-lg-4">
        <x-ui.card title="Distribución por tipo" icon="pie-chart">
            @forelse($porTipo as $tipo)
                <div class="d-flex justify-content-between align-items-center border-bottom border-light py-2">
                    <span class="text-truncate">{{ $tipo->tipo_licencia ?: 'SIN TIPO' }}</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $tipo->total }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">Sin licencias registradas.</p>
            @endforelse
        </x-ui.card>
    </div>

    {{-- Por vencer --}}
    <div class="col-12 col-lg-8">
        <x-ui.card title="Próximas a vencer (60 días)" icon="calendar-week">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Licencia</th>
                            <th>Tipo</th>
                            <th>Vence</th>
                            <th class="text-end">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porVencer as $lic)
                            @php $dias = \Carbon\Carbon::parse($lic->fecha_vencimiento)->startOfDay()->diffInDays(now()->startOfDay(), false); @endphp
                            <tr>
                                <td class="fw-medium">
                                    <a class="text-decoration-none" href="{{ route('licencias.show', $lic) }}">{{ $lic->nombre }}</a>
                                </td>
                                <td class="small text-muted">{{ $lic->tipo_licencia ?: '—' }}</td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($lic->fecha_vencimiento)->format('d/m/Y') }}
                                    <span class="badge ms-1 {{ $dias <= 30 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $dias }} días</span>
                                </td>
                                <td class="text-end">{{ $lic->estado }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nada por vencer en los próximos 60 días. ✔</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- Vencidas recientes --}}
    <div class="col-12 col-lg-6">
        <x-ui.card title="Vencidas recientes" icon="x-octagon">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Licencia</th><th>Venció</th></tr>
                    </thead>
                    <tbody>
                        @forelse($vencidasRecientes as $lic)
                            <tr>
                                <td class="fw-medium"><a class="text-decoration-none" href="{{ route('licencias.show', $lic) }}">{{ $lic->nombre }}</a></td>
                                <td class="text-nowrap text-muted">{{ $lic->fecha_vencimiento ? \Carbon\Carbon::parse($lic->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Sin licencias vencidas. ✔</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- Más asignadas --}}
    <div class="col-12 col-lg-6">
        <x-ui.card title="Licencias con más asignaciones" icon="award">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Licencia</th><th class="text-end">Asignaciones</th></tr>
                    </thead>
                    <tbody>
                        @forelse($masUsadas as $lic)
                            <tr>
                                <td class="fw-medium">
                                    <a class="text-decoration-none" href="{{ route('licencias.show', $lic) }}">{{ $lic->nombre }}</a>
                                    <small class="text-muted d-block">{{ $lic->tipo_licencia ?: '—' }}</small>
                                </td>
                                <td class="text-end"><span class="badge bg-primary">{{ $lic->asignaciones_count }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Sin asignaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
