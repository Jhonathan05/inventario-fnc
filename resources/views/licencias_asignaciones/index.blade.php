@extends('layouts.inventario')

@section('title', 'Asignaciones de Licencias')

@section('content')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
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
            <div class="col-lg-4">
                <label class="form-label fw-medium small mb-1">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="Activa" {{ request('estado') === 'Activa' ? 'selected' : '' }}>Activa</option>
                    <option value="Vencida" {{ request('estado') === 'Vencida' ? 'selected' : '' }}>Vencida</option>
                    <option value="Retirada" {{ request('estado') === 'Retirada' ? 'selected' : '' }}>Retirada</option>
                    <option value="Suspendida" {{ request('estado') === 'Suspendida' ? 'selected' : '' }}>Suspendida</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2 flex-wrap">
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
                                @if($asignacion->funcionario)
                                    @php
                                        $inicialesAsigLic = collect(explode(' ', trim((string) $asignacion->funcionario->nombre_completo)))
                                            ->reject(fn ($p) => mb_strlen($p) < 2)
                                            ->map(fn ($p) => mb_substr($p, 0, 1))
                                            ->take(2)
                                            ->implode('');
                                    @endphp
                                    <span class="fnc-udash__user">
                                        <span class="fnc-udash__avatar" aria-hidden="true">{{ $inicialesAsigLic ?: '·' }}</span>
                                        <span class="fnc-udash__unombre">{{ $asignacion->funcionario->nombre_completo }}</span>
                                    </span>
                                @else
                                    <span class="fnc-udash__sub">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($asignacion->equipo)
                                    <span class="fnc-udash__names">
                                        <a href="{{ route('equipos.show', $asignacion->equipo) }}" class="fnc-udash__name">
                                            {{ $asignacion->equipo->placa_visual ?? $asignacion->equipo->nombre_equipo ?? 'Ver Equipo' }}
                                        </a>
                                        <span class="fnc-udash__sub">Serial: {{ $asignacion->equipo->serial_visual ?? 'N/A' }}</span>
                                    </span>
                                @else
                                    <span class="fnc-udash__sub">N/A</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <span class="fnc-udash__when">
                                    <span class="fnc-udash__whenrel {{ $asignacion->fecha_vencimiento && $asignacion->fecha_vencimiento->isPast() ? 'text-danger' : '' }}">
                                        Vence {{ $asignacion->fecha_vencimiento ? $asignacion->fecha_vencimiento->diffForHumans() : 'N/A' }}
                                    </span>
                                    <span class="fnc-udash__sub">Asignada: {{ $asignacion->fecha_asignacion?->format('d/m/Y') ?? 'N/A' }} · {{ $asignacion->fecha_vencimiento?->format('d/m/Y') ?? 'N/A' }}</span>
                                </span>
                            </td>
                            <td>
                                <x-fnc.pill :tone="$asignacion->estado === 'Activa' ? 'success' : ($asignacion->estado === 'Vencida' ? 'danger' : 'slate')" dot>
                                    {{ $asignacion->estado }}
                                </x-fnc.pill>
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
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 p-3 border-top"><small class="text-muted"> Mostrando {{ $asignaciones->firstItem() }}–{{ $asignaciones->lastItem() }} de {{ $asignaciones->total() }} asignaciones
            </small>
            {{ $asignaciones->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

