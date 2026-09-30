@extends('layouts.inventario')

@section('title', 'Historial de Versiones')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-clock-history me-2 text-info"></i>
        Historial de Versiones: {{ $acta->numero_acta }}
    </h4>
    <a href="{{ route('actas-firmadas.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title text-success"><i class="bi bi-file-earmark-check me-2"></i>Versión Actual</h5>
        <div class="row mt-3">
            <div class="col-md-3"><strong>Subido Por:</strong><br>{{ $acta->user->name ?? 'Sistema' }}</div>
            <div class="col-md-3"><strong>Fecha Documento:</strong><br>{{ $acta->fecha_documento->format('d/m/Y') }}</div>
            <div class="col-md-3"><strong>Tipo:</strong><br>{{ $acta->tipo_acta }}</div>
            <div class="col-md-3">
                <strong>Acción:</strong><br>
                <a href="{{ route('actas-firmadas.download', $acta->id) }}" class="btn btn-sm btn-outline-primary mt-1">
                    <i class="bi bi-download me-1"></i>Descargar Actual
                </a>
            </div>
        </div>
    </div>
</div>

<h5 class="mb-3 text-muted">Versiones Anteriores</h5>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Versión</th>
                        <th>Reemplazado Por</th>
                        <th>Fecha de Reemplazo</th>
                        <th>Motivo del Cambio</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($acta->versions as $version)
                        <tr>
                            <td class="fw-bold">v{{ $version->version }}</td>
                            <td>{{ $version->user->name ?? 'Sistema' }}</td>
                            <td>{{ $version->created_at->format('d/m/Y H:i') }}</td>
                            <td><small>{{ $version->motivo_cambio }}</small></td>
                            <td class="text-center">
                                <a href="{{ route('actas-firmadas.download-version', $version->id) }}" class="btn btn-sm btn-outline-secondary" title="Descargar Versión {{ $version->version }}">
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>Esta acta no ha sido reemplazada. No hay versiones anteriores.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

