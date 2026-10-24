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
    <x-ui.filter-container action="{{ route('prestamos.index') }}" clearRoute="{{ route('prestamos.index') }}">
        <div class="col-12 col-lg-5">
            <label class="form-label fw-medium small mb-1">Buscar por Activo o Persona</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" enterkeyhint="search" name="buscar" value="{{ request('buscar') }}"
                       class="form-control" placeholder="Placa, serial, persona..." autocomplete="off">
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label fw-medium small mb-1">Estado</label>
            <select name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="Activo" {{ request('estado') == 'Activo' ? 'selected' : '' }}>Activo</option>
                <option value="Vencido" {{ request('estado') == 'Vencido' ? 'selected' : '' }}>Vencido</option>
                <option value="Devuelto" {{ request('estado') == 'Devuelto' ? 'selected' : '' }}>Devuelto</option>
                <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>
    </x-ui.filter-container>

    <!-- Tabla -->
    <x-ui.card noPadding="true">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th>ID</th>
                    <th>Activo</th>
                    <th>Prestado A</th>
                    <th>Inicio</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th class="fnc-fhd-only">Registrado por</th>
                    <th class="fnc-fhd-only">Motivo</th>
                    <th class="fnc-fhd-only">Duración</th>
                    <th class="fnc-fhd-only">Devolución real</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </x-slot:head>
                        @forelse($prestamos as $prestamo)
                            @php
                                $tipoNombre = mb_strtoupper($prestamo->equipo?->tipoRecurso?->nombre ?? '');
                                $iconoPrestamo = match (true) {
                                    str_contains($tipoNombre, 'PORTATIL') => 'laptop',
                                    str_contains($tipoNombre, 'ESCRITORIO'),
                                    str_contains($tipoNombre, 'TODO EN UNO'),
                                    str_contains($tipoNombre, 'MONITOR') => 'monitor',
                                    str_contains($tipoNombre, 'TABLET') => 'tablet',
                                    str_contains($tipoNombre, 'IMPRESOR') => 'printer',
                                    str_contains($tipoNombre, 'CAJON'),
                                    str_contains($tipoNombre, 'CAJÓN') => 'package',
                                    str_contains($tipoNombre, 'SERVIDOR') => 'server',
                                    str_contains($tipoNombre, 'SMART'),
                                    str_contains($tipoNombre, 'TELEF') => 'smartphone',
                                    str_contains($tipoNombre, 'TECLADO') => 'keyboard',
                                    str_contains($tipoNombre, 'MOUSE') => 'mouse',
                                    str_contains($tipoNombre, 'DIADEMA'),
                                    str_contains($tipoNombre, 'AURICULAR'),
                                    str_contains($tipoNombre, 'HEADSET') => 'headphones',
                                    default => 'boxes',
                                };
                                $inicialesPersona = collect(explode(' ', trim((string) $prestamo->persona_nombre)))
                                    ->reject(fn ($p) => mb_strlen($p) < 2)
                                    ->map(fn ($p) => mb_substr($p, 0, 1))
                                    ->take(2)
                                    ->implode('');
                                $tonePrestamo = match ($prestamo->estado) {
                                    'Activo' => 'info',
                                    'Vencido' => 'danger',
                                    'Devuelto' => 'success',
                                    'Cancelado' => 'slate',
                                    default => 'slate',
                                };
                            @endphp
                            <tr>
                                <td class="text-nowrap"><span class="fnc-udash__code">#{{ str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td class="text-nowrap">
                                    @if($prestamo->equipo)
                                        <div class="fnc-udash__asset">
                                            <span class="fnc-udash__ic" aria-hidden="true">
                                                <x-fnc.icon :name="$iconoPrestamo" :size="16" />
                                            </span>
                                            <span class="fnc-udash__names">
                                                <a href="{{ route('equipos.show', $prestamo->equipo) }}" class="fnc-udash__name">
                                                    {{ $prestamo->equipo->placa_visual }}
                                                </a>
                                                <span class="fnc-udash__sub">{{ Str::limit($prestamo->equipo->nombre_equipo, 25) }}</span>
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-danger">Equipo Eliminado</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fnc-udash__user">
                                        <span class="fnc-udash__avatar" aria-hidden="true">{{ $inicialesPersona ?: '·' }}</span>
                                        <span class="fnc-udash__uname">
                                            <span class="fnc-udash__unombre">{{ $prestamo->persona_nombre }}</span>
                                            <span class="fnc-udash__sub">{{ $prestamo->persona_documento }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fnc-udash__when">
                                        <span class="fnc-udash__whenrel">{{ $prestamo->fecha_inicio->diffForHumans() }}</span>
                                        <span class="fnc-udash__sub">{{ $prestamo->fecha_inicio->format('d/m/Y H:i') }}</span>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    @php
                                        $isPast = $prestamo->fecha_devolucion_prevista < now();
                                        $isToday = $prestamo->fecha_devolucion_prevista->isToday();
                                    @endphp
                                    <span class="fnc-udash__when">
                                        <span class="fnc-udash__whenrel {{ $isPast ? 'text-danger' : ($isToday ? 'text-warning' : '') }}">
                                            @if(in_array($prestamo->estado, ['Activo', 'Pendiente']))
                                                @if($isPast)
                                                    Vencido {{ $prestamo->fecha_devolucion_prevista->diffForHumans() }}
                                                @elseif($isToday)
                                                    Vence hoy a las {{ $prestamo->fecha_devolucion_prevista->format('H:i') }}
                                                @else
                                                    Vence {{ $prestamo->fecha_devolucion_prevista->diffForHumans() }}
                                                @endif
                                            @else
                                                {{ $prestamo->fecha_devolucion_prevista->diffForHumans() }}
                                            @endif
                                        </span>
                                        <span class="fnc-udash__sub">{{ $prestamo->fecha_devolucion_prevista->format('d/m/Y H:i') }}</span>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <x-fnc.pill :tone="$tonePrestamo" dot>{{ $prestamo->estado }}</x-fnc.pill>
                                </td>
                                <td class="fnc-fhd-only text-nowrap">{{ $prestamo->registradoPor?->name ?? '—' }}</td>
                                <td class="fnc-fhd-only">
                                    <span class="fnc-udash__sub d-inline-block text-truncate" style="max-width: 220px;" title="{{ $prestamo->motivo }}">
                                        {{ $prestamo->motivo ?: '—' }}
                                    </span>
                                </td>
                                <td class="fnc-fhd-only text-nowrap">{{ $prestamo->duracion ?: '—' }}</td>
                                <td class="fnc-fhd-only text-nowrap">
                                    @if($prestamo->fecha_devolucion_real)
                                        <span class="fnc-udash__when">
                                            <span class="fnc-udash__whenrel">{{ $prestamo->fecha_devolucion_real->diffForHumans() }}</span>
                                            <span class="fnc-udash__sub">{{ $prestamo->fecha_devolucion_real->format('d/m/Y H:i') }}</span>
                                        </span>
                                    @else
                                        <span class="fnc-udash__sub">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('prestamos.show', $prestamo) }}" class="fnc-udash__ver" title="Ver detalle" aria-label="Ver detalle del préstamo #{{ str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) }}">
                                        <x-fnc.icon name="eye" :size="15" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No se encontraron préstamos registrados.</td>
                            </tr>
                        @endforelse
        </x-ui.table>

        @if($prestamos->hasPages())
            <x-slot name="footer">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                    <small class="text-muted text-center text-md-start">
                        Mostrando {{ $prestamos->firstItem() }}–{{ $prestamos->lastItem() }} de {{ $prestamos->total() }} préstamos
                    </small>
                    {{ $prestamos->links() }}
                </div>
            </x-slot>
        @endif
    </x-ui.card>
</div>
@endsection
