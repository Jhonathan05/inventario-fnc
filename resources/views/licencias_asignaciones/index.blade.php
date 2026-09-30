@extends('layouts.inventario')

@section('title', 'Asignaciones de Licencias')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Asignaciones de Licencias</h4>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('licencias.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Catálogo de Licencias
        </a>
        <a href="{{ route('licencia-asignaciones.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva Asignación
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('licencia-asignaciones.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-medium small mb-1">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="Activa" {{ request('estado') === 'Activa' ? 'selected' : '' }}>Activa</option>
                    <option value="Vencida" {{ request('estado') === 'Vencida' ? 'selected' : '' }}>Vencida</option>
                    <option value="Retirada" {{ request('estado') === 'Retirada' ? 'selected' : '' }}>Retirada</option>
                    <option value="Suspendida" {{ request('estado') === 'Suspendida' ? 'selected' : '' }}>Suspendida</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="{{ route('licencia-asignaciones.index') }}" class="btn btn-outline-secondary">
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
                        <th>ID</th>
                        <th>Licencia</th>
                        <th>Funcionario</th>
                        <th>Equipo</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignaciones as $asignacion)
                        <tr>
                            <td class="text-muted small text-nowrap">{{ $asignacion->id }}</td>
                            <td>
                                @if($asignacion->licencia)
                                    <a href="{{ route('licencias.show', $asignacion->licencia) }}" class="fw-medium text-decoration-none">
                                        {{ $asignacion->licencia->nombre }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                {{ $asignacion->funcionario ? $asignacion->funcionario->nombre_completo : 'N/A' }}
                            </td>
                            <td>
                                @if($asignacion->equipo)
                                    <a href="{{ route('equipos.show', $asignacion->equipo) }}" class="text-decoration-none fw-medium">
                                        {{ $asignacion->equipo->placa_visual ?? $asignacion->equipo->nombre_equipo ?? 'Ver Equipo' }}
                                    </a><br>
                                    <span class="text-muted small">Serial: {{ $asignacion->equipo->serial_visual ?? 'N/A' }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">Asignada:</small> {{ $asignacion->fecha_asignacion ? $asignacion->fecha_asignacion->format('d/m/Y') : 'N/A' }}<br>
                                <small class="text-muted">Vence:</small> <strong class="{{ $asignacion->fecha_vencimiento && $asignacion->fecha_vencimiento->isPast() ? 'text-danger' : '' }}">{{ $asignacion->fecha_vencimiento ? $asignacion->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $asignacion->estado === 'Activa' ? 'success' : ($asignacion->estado === 'Vencida' ? 'danger' : 'secondary') }}">
                                    {{ $asignacion->estado }}
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="d-flex gap-1 justify-content-center flex-wrap">
                                    <a href="{{ route('licencia-asignaciones.edit', $asignacion) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar"
                                            data-delete-url="{{ route('licencia-asignaciones.destroy', $asignacion) }}"
                                            data-delete-name="Asignación ID {{ $asignacion->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay asignaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($asignaciones->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $asignaciones->firstItem() }}–{{ $asignaciones->lastItem() }} de {{ $asignaciones->total() }} asignaciones
            </small>
            {{ $asignaciones->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

