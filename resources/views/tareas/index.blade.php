@extends('layouts.inventario')

@section('title', 'Tareas')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Tareas"
        icon="list-checks"
        subtitle="Tablero de pendientes por prioridad y derivadas de la CMDB"
    />

    {{-- Cabecera ejecutiva (patrón SSP): KPIs del tablero --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Pendientes</div>
                    <div class="fs-4 fw-bold">{{ number_format($totalPendientes) }}</div>
                    <div class="text-muted small">{{ $totalVisibles }} visibles con los filtros</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Prioridad alta</div>
                    <div class="fs-4 fw-bold text-danger">
                        {{ number_format($columnas->firstWhere('key', 'Alta')['items']->count()) }}
                    </div>
                    <div class="text-muted small">requieren atención primero</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Derivadas CMDB</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format($columnas->firstWhere('key', 'CMDB')['items']->count()) }}
                    </div>
                    <div class="text-muted small">se resuelven corrigiendo el dato</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Completadas</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($hechas->count()) }}</div>
                    <div class="text-muted small">últimas {{ $hechas->count() }} tareas hechas</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Alta rápida (misma ruta del panel del dashboard) --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.08em;">Nueva tarea</div>
                    <form method="POST" action="{{ route('tareas.store') }}" class="d-flex gap-2 flex-wrap">
                        @csrf
                        <input type="text" name="titulo" class="form-control flex-grow-1" minlength="4"
                               maxlength="160" required placeholder="Describe la tarea pendiente…" aria-label="Descripción de la tarea">
                        <select name="prioridad" class="form-select" style="max-width: 130px;" aria-label="Prioridad">
                            <option value="Alta">Alta</option>
                            <option value="Media" selected>Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                        <select name="modulo" class="form-select" style="max-width: 150px;" aria-label="Módulo">
                            <option value="general" selected>General</option>
                            <option value="equipos">Equipos</option>
                            <option value="funcionarios">Funcionarios</option>
                        </select>
                        <button type="submit" class="btn btn-primary" title="Crear la tarea" aria-label="Crear la tarea">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Filtros GET idempotentes (reflejan el estado en la URL) --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.08em;">Filtros</div>
                    <form method="GET" action="{{ route('tareas.index') }}" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="q" class="form-control flex-grow-1" style="min-width: 160px;"
                               value="{{ $filtros['q'] }}" placeholder="Buscar por título…" aria-label="Buscar por título">
                        <select name="origen" class="form-select" style="max-width: 170px;" aria-label="Origen">
                            <option value="todas" {{ $filtros['origen'] === 'todas' ? 'selected' : '' }}>Todos los orígenes</option>
                            <option value="manuales" {{ $filtros['origen'] === 'manuales' ? 'selected' : '' }}>Sólo manuales</option>
                            <option value="derivadas" {{ $filtros['origen'] === 'derivadas' ? 'selected' : '' }}>Sólo derivadas CMDB</option>
                        </select>
                        <select name="modulo" class="form-select" style="max-width: 180px;" aria-label="Módulo">
                            @foreach ($modulos as $opcion)
                                <option value="{{ $opcion['valor'] }}" {{ $filtros['modulo'] === $opcion['valor'] ? 'selected' : '' }}>
                                    {{ $opcion['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-dark" title="Aplicar filtros" aria-label="Aplicar filtros">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                        @if ($filtros['origen'] !== 'todas' || $filtros['modulo'] !== 'todos' || $filtros['q'] !== '')
                            <a href="{{ route('tareas.index') }}" class="btn btn-outline-secondary" title="Quitar filtros" aria-label="Quitar filtros">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban por prioridad + derivadas CMDB --}}
    <div class="fnc-kanban mb-4">
        @foreach ($columnas as $columna)
            <section class="fnc-kanban__col" aria-label="{{ $columna['label'] }}">
                <header class="fnc-kanban__head">
                    <x-fnc.pill :tone="$columna['tone']" dot>{{ $columna['key'] }}</x-fnc.pill>
                    <span class="fnc-kanban__label">{{ $columna['label'] }}</span>
                    <span class="fnc-kanban__count">{{ $columna['items']->count() }}</span>
                </header>

                @forelse ($columna['items'] as $item)
                    <article class="fnc-kanban__card" style="--fnc-tone: var(--fnc-{{ $columna['tone'] }});">
                        <p class="fnc-kanban__card-title mb-0">{{ $item['titulo'] }}</p>
                        <p class="fnc-kanban__card-meta mb-0">{{ $item['meta'] }}</p>
                        <footer class="fnc-kanban__card-foot">
                            @if ($item['kind'] === 'manual')
                                <span class="fnc-udash__sub">{{ $item['cuando'] }}</span>
                                <span class="d-inline-flex gap-1 ms-auto">
                                    <form method="POST" action="{{ route('tareas.toggle', $item['tarea_id']) }}">
                                        @csrf
                                        <button type="submit" class="fnc-kanban__accion" title="Marcar como hecha"
                                                aria-label="Marcar como hecha: {{ $item['titulo'] }}">
                                            <x-fnc.icon name="circle-check" :size="16" />
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('tareas.destroy', $item['tarea_id']) }}"
                                          onsubmit="return confirm('¿Eliminar la tarea «{{ $item['titulo'] }}»?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fnc-kanban__accion fnc-kanban__accion--peligro"
                                                title="Eliminar" aria-label="Eliminar: {{ $item['titulo'] }}">
                                            <x-fnc.icon name="trash" :size="16" />
                                        </button>
                                    </form>
                                </span>
                            @else
                                <span class="fnc-udash__sub">{{ Str::ucfirst($item['modulo']) }}</span>
                                <a href="{{ $item['url'] }}" class="fnc-kanban__accion ms-auto" title="Corregir el dato de origen"
                                   aria-label="Corregir el dato de origen: {{ $item['titulo'] }}">
                                    <x-fnc.icon name="chevron-right" :size="16" />
                                </a>
                            @endif
                        </footer>
                    </article>
                @empty
                    <div class="fnc-kanban__vacio">
                        <x-fnc.icon name="circle-check" :size="18" />
                        Sin pendientes en esta columna.
                    </div>
                @endforelse
            </section>
        @endforeach
    </div>

    {{-- Hechas recientemente: reabrir o eliminar (transiciones del tablero) --}}
    <x-ui.card noPadding="true" title="Hechas recientemente" icon="circle-check">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="ps-4">Tarea</th>
                    <th>Completada</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </x-slot>
            @forelse ($hechas as $tarea)
                <tr>
                    <td class="ps-4">
                        <div class="fnc-udash__asset">
                            <span class="fnc-udash__ic" aria-hidden="true">
                                <x-fnc.icon name="circle-check" :size="16" />
                            </span>
                            <span class="fnc-udash__names">
                                <span class="fnc-udash__name">{{ $tarea->titulo }}</span>
                                <span class="fnc-udash__sub">{{ Str::ucfirst($tarea->modulo) }} · {{ $tarea->prioridad }}</span>
                            </span>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <span class="fnc-udash__when">
                            <span class="fnc-udash__whenrel">{{ $tarea->hecha_en?->diffForHumans() }}</span>
                            <span class="fnc-udash__sub">{{ $tarea->hecha_en?->format('d/m/Y H:i') }}</span>
                        </span>
                    </td>
                    <td class="text-end pe-4 text-nowrap">
                        <span class="d-inline-flex gap-1 justify-content-end">
                            <form method="POST" action="{{ route('tareas.toggle', $tarea) }}">
                                @csrf
                                <button type="submit" class="fnc-kanban__accion" title="Reabrir"
                                        aria-label="Reabrir: {{ $tarea->titulo }}">
                                    <x-fnc.icon name="refresh-cw" :size="16" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('tareas.destroy', $tarea) }}"
                                  onsubmit="return confirm('¿Eliminar la tarea «{{ $tarea->titulo }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="fnc-kanban__accion fnc-kanban__accion--peligro"
                                        title="Eliminar" aria-label="Eliminar: {{ $tarea->titulo }}">
                                    <x-fnc.icon name="trash" :size="16" />
                                </button>
                            </form>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center py-5 text-muted">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 opacity-50"></i>
                        Aún no hay tareas completadas. Las que marques como hechas aparecerán aquí.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
@endsection
