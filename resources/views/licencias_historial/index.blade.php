@extends('layouts.inventario')

@section('title', 'Historial de Licencias')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Historial de Licencias</h4>
    <a href="{{ route('licencias.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al Catálogo
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('licencias.historial') }}" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label fw-medium small mb-1">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="form-control" placeholder="Licencia, funcionario, equipo o acción...">
                </div>
            </div>
            <div class="col-lg-2 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Buscar
                </button>
                <a href="{{ route('licencias.historial') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario (Responsable)</th>
                        <th>Acción</th>
                        <th>Licencia</th>
                        <th>Funcionario</th>
                        <th>Equipo (Placa)</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $log)
                    @php
                        $accionTxtLic = mb_strtolower((string) $log->accion);
                        $toneAccionLic = match (true) {
                            str_contains($accionTxtLic, 'cre') || str_contains($accionTxtLic, 'asign') => 'success',
                            str_contains($accionTxtLic, 'elimin') || str_contains($accionTxtLic, 'retir') || str_contains($accionTxtLic, 'borr') => 'danger',
                            str_contains($accionTxtLic, 'actualiz') || str_contains($accionTxtLic, 'edit') || str_contains($accionTxtLic, 'susp') => 'info',
                            default => 'slate',
                        };
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            <span class="fnc-udash__when">
                                <span class="fnc-udash__whenrel">{{ $log->fecha->diffForHumans() }}</span>
                                <span class="fnc-udash__sub">{{ $log->fecha->format('d/m/Y H:i:s') }}</span>
                            </span>
                        </td>
                        <td>
                            <span class="fnc-udash__user">
                                <span class="fnc-udash__avatar" aria-hidden="true">{{ $log->usuario ? mb_substr($log->usuario->name, 0, 1) : '·' }}</span>
                                <span class="fnc-udash__unombre">{{ $log->usuario ? $log->usuario->name : 'Sistema' }}</span>
                            </span>
                        </td>
                        <td>
                            <x-fnc.pill :tone="$toneAccionLic">{{ $log->accion }}</x-fnc.pill>
                        </td>
                        <td>
                            <span class="fnc-udash__names">
                                <span class="fnc-udash__name">{{ $log->licencia_nombre ?? 'N/A' }}</span>
                            </span>
                        </td>
                        <td>{{ $log->funcionario_nombre ?? 'N/A' }}</td>
                        <td><span class="fnc-udash__code">{{ $log->equipo_placa ?? 'N/A' }}</span></td>
                        <td class="small text-muted">{{ $log->observacion }}</td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay registros en el historial.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($historial->hasPages())
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $historial->firstItem() }}–{{ $historial->lastItem() }} de {{ $historial->total() }} registros
            </small>
            {{ $historial->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection


