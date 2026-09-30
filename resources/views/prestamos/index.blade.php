@extends('layouts.inventario')

@section('title', 'Préstamos de Activos')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar 
        title="Préstamos de Activos" 
        icon="calendar2-range" 
        createRoute="{{ auth()->user()->can('equipos.crear') ? route('prestamos.create') : null }}" 
        createText="Registrar Préstamo" 
    />

    <!-- Filtros y Búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">
            <form action="{{ route('prestamos.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Buscar por Activo o Persona</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" class="form-control border-start-0" placeholder="Placa, serial, persona..." value="{{ request('buscar') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="Activo" {{ request('estado') == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="Vencido" {{ request('estado') == 'Vencido' ? 'selected' : '' }}>Vencido</option>
                        <option value="Devuelto" {{ request('estado') == 'Devuelto' ? 'selected' : '' }}>Devuelto</option>
                        <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                </div>
                @if(request()->anyFilled(['buscar', 'estado']))
                    <div class="col-md-2">
                        <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>ID</th>
                            <th>Activo</th>
                            <th>Prestado A</th>
                            <th>Inicio</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prestamos as $prestamo)
                            <tr>
                                <td class="text-muted fw-medium text-nowrap">#{{ str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="text-nowrap">
                                    @if($prestamo->equipo)
                                        <a href="{{ route('equipos.show', $prestamo->equipo) }}" class="fw-semibold text-decoration-none">
                                            {{ $prestamo->equipo->placa_visual }}
                                        </a>
                                        <div class="small text-muted">{{ Str::limit($prestamo->equipo->nombre_equipo, 25) }}</div>
                                    @else
                                        <span class="text-danger">Equipo Eliminado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $prestamo->persona_nombre }}</div>
                                    <div class="small text-muted">{{ $prestamo->persona_documento }}</div>
                                </td>
                                <td class="text-nowrap">{{ $prestamo->fecha_inicio->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">
                                    {{ $prestamo->fecha_devolucion_prevista->format('d/m/Y H:i') }}
                                    @if(in_array($prestamo->estado, ['Activo', 'Pendiente']))
                                        @php
                                            $isPast = $prestamo->fecha_devolucion_prevista < now();
                                            $isToday = $prestamo->fecha_devolucion_prevista->isToday();
                                        @endphp
                                        <div class="small {{ $isPast ? 'text-danger fw-bold' : ($isToday ? 'text-warning text-dark fw-bold' : 'text-muted') }}">
                                            @if($isPast)
                                                Vencido {{ $prestamo->fecha_devolucion_prevista->diffForHumans() }}
                                            @elseif($isToday)
                                                Vence hoy a las {{ $prestamo->fecha_devolucion_prevista->format('H:i') }}
                                            @else
                                                Vence {{ $prestamo->fecha_devolucion_prevista->diffForHumans() }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge bg-{{ $prestamo->estado_badge }} px-2 py-1">{{ $prestamo->estado }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('prestamos.show', $prestamo) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No se encontraron préstamos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($prestamos->hasPages())
            <div class="card-footer bg-white">
                {{ $prestamos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
