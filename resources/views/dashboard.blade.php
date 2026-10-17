@extends('layouts.inventario')

@section('title', 'Panel de control')

@php
    /** @var \Illuminate\Support\Carbon $hoy */
    $hoy = now()->startOfDay();

    // Donut: top 6 categorías + agrupación del resto para que la suma cuadre.
    $segmentos = $inventario['porTipo']['segmentos'] ?? [];
    $topCategorias = array_slice($segmentos, 0, 6);
    $sumaTop = (int) array_sum(array_column($topCategorias, 'value'));
    $totalInventario = (int) ($inventario['total'] ?? 0);
    $otros = max(0, $totalInventario - $sumaTop);
    if ($otros > 0) {
        $topCategorias[] = [
            'label' => 'Otras categorías',
            'value' => $otros,
            'color' => '#64748b',
            'url' => route('equipos.index', [], false),
            'pct' => $totalInventario > 0 ? round($otros / $totalInventario * 100, 1) : 0.0,
        ];
    }

    // Ventana de vencimientos: control segmentado (vive en la topbar).
    $opcionesVentana = collect($ventanas ?? [30])->map(fn ($dias) => [
        'value' => $dias,
        'label' => $dias . ' d',
    ])->all();

    // Barra de estado del parque (con enlace a la vista filtrada).
    $filasEstado = collect($inventario['filas'] ?? [])->map(function (array $fila) {
        $fila['url'] = route('equipos.index', ['estado' => $fila['estado']], false);

        return $fila;
    })->all();

    $serieTickets = collect($tickets['serie'] ?? [])->map(fn ($punto) => [
        'label' => $punto['label'],
        'value' => $punto['value'],
    ])->all();

    // Próximos vencimientos: se unifican licencias, garantías y préstamos en una
    // sola lista ordenada por fecha (equivale a "My Meetings" de la referencia).
    $vencimientos = collect();

    if (! empty($licencias['criticas'])) {
        foreach ($licencias['criticas'] as $licencia) {
            $vence = $licencia->fecha_vencimiento ? \Illuminate\Support\Carbon::parse($licencia->fecha_vencimiento) : null;
            $dias = $vence ? (int) $hoy->diffInDays($vence->copy()->startOfDay(), false) : null;
            $vencimientos->push([
                'kicker' => 'Licencia',
                'day' => $vence ? $vence->format('d/m') : '—',
                'title' => $licencia->nombre,
                'meta' => $vence ? 'Vence el ' . $vence->format('d/m/Y') : 'Sin fecha de vencimiento',
                'tone' => $dias !== null && $dias < 0 ? 'danger' : 'warning',
                'url' => route('licencias.index', ['estado' => $licencia->estado === 'Vencida' ? 'Vencida' : 'Activa'], false),
                'orden' => $vence ? $vence->timestamp : PHP_INT_MAX,
            ]);
        }
    }

    if (! empty($garantias['cola'])) {
        foreach ($garantias['cola'] as $equipo) {
            $vence = $equipo->fin_garantia ? \Illuminate\Support\Carbon::parse($equipo->fin_garantia) : null;
            $dias = $vence ? (int) $hoy->diffInDays($vence->copy()->startOfDay(), false) : null;
            $vencimientos->push([
                'kicker' => 'Garantía',
                'day' => $vence ? $vence->format('d/m') : '—',
                'title' => $equipo->nombre_equipo ?: ($equipo->placa ?: 'Activo #' . $equipo->id),
                'meta' => collect([$equipo->tipoRecurso?->nombre, $equipo->marca, $equipo->modelo])->filter()->implode(' · '),
                'tone' => $dias !== null && $dias <= 15 ? 'warning' : 'info',
                'url' => route('equipos.show', $equipo, false),
                'orden' => $vence ? $vence->timestamp : PHP_INT_MAX,
            ]);
        }
    }

    if (! empty($prestamos['cola'])) {
        foreach ($prestamos['cola'] as $prestamo) {
            $fecha = $prestamo->fecha_devolucion_prevista;
            $dias = $fecha ? (int) $hoy->diffInDays($fecha->copy()->startOfDay(), false) : null;
            $vencimientos->push([
                'kicker' => 'Devolución',
                'day' => $fecha ? $fecha->format('d/m') : '—',
                'title' => $prestamo->persona_nombre ?: 'Préstamo #' . $prestamo->id,
                'meta' => collect([$prestamo->equipo?->nombre_equipo, $prestamo->equipo?->placa])->filter()->implode(' · ') ?: 'Equipo #' . $prestamo->equipo_id,
                'tone' => $dias !== null && $dias < 0 ? 'danger' : 'info',
                'url' => route('prestamos.index', ['estado' => 'Activo'], false),
                'orden' => $fecha ? $fecha->timestamp : PHP_INT_MAX,
            ]);
        }
    }

    $vencimientos = $vencimientos->sortBy('orden')->take(6)->values();
@endphp

{{-- Centro de la topbar: selector de ventana + buscador global --}}
@section('topbar-center')
    <x-fnc.segmented
        class="d-none d-md-inline-flex"
        :items="$opcionesVentana"
        :current="$ventana"
        param="ventana"
        aria-label="{{ 'Ventana de vencimientos (' . $ventana . ' días)' }}" />

    <button class="topbar-search" type="button" id="fncPaletteTrigger"
            aria-haspopup="dialog" aria-controls="fncPalette" aria-expanded="false"
            title="Buscar módulo o acción (Ctrl + K)">
        <x-fnc.icon name="search" :size="16" />
        <span class="topbar-search__text d-none d-sm-inline">Buscar módulo o acción…</span>
        <span class="fnc-kbd d-none d-lg-inline-flex">Ctrl</span>
        <span class="fnc-kbd d-none d-lg-inline-flex">K</span>
    </button>
@endsection

@section('content')
<div class="fnc-dash">

    {{-- ============================= Cabecera ============================= --}}
    <header class="fnc-page-head">
        <div>
            <span class="fnc-page-head__eyebrow">
                <x-fnc.icon name="gauge" :size="13" />
                ITAM · Federación Nacional de Cafeteros — Tolima
            </span>
            <h1 class="fnc-page-head__title">Panel de control</h1>
            <p class="fnc-page-head__sub">
                Estado del inventario tecnológico y de su operación diaria.
            </p>
        </div>

        <div class="fnc-page-head__actions">
            @can('dashboard.ver')
                <a class="fnc-btn" href="{{ route('reportes.index') }}">
                    <x-fnc.icon name="chart-column" :size="15" />
                    Reportes
                </a>
            @endcan

            <a class="fnc-icon-btn" href="{{ route('dashboard') }}" title="Actualizar datos" aria-label="Actualizar datos">
                <x-fnc.icon name="refresh-cw" :size="16" />
            </a>
        </div>
    </header>

    {{-- ========================= Centro de alertas ======================== --}}
    @if (! empty($alertas))
        <div class="fnc-dash__alerts" role="list" aria-label="Alertas accionables">
            @foreach ($alertas as $alerta)
                <x-fnc.alert-row
                    :tone="$alerta['tone']"
                    :icon="$alerta['icon']"
                    :title="$alerta['title']"
                    :meta="$alerta['meta']"
                    :count="$alerta['count']"
                    :url="$alerta['url']" />
            @endforeach
        </div>
    @else
        <x-fnc.panel class="fnc-panel--flat">
            <x-fnc.empty
                icon="circle-check"
                title="Sin incidencias accionables"
                text="No hay licencias vencidas, préstamos atrasados, responsabilidades cumplidas ni tickets críticos pendientes en este momento." />
        </x-fnc.panel>
    @endif

    {{-- ======================= Tablero de 6 paneles ======================= --}}
    <div class="fnc-dash__board">

        {{-- Categorías (equivale a "My Tasks"): los indicadores del inventario --}}
        @if (! empty($kpis))
            <x-fnc.panel
                title="Categorías"
                sub="Indicadores del inventario"
                icon="layout-grid"
                class="fnc-board__cats">
                <x-slot:actions>
                    @can('dashboard.ver')
                        <a class="fnc-icon-btn" href="{{ route('reportes.index') }}" title="Ver reportes" aria-label="Ver reportes">
                            <x-fnc.icon name="chart-column" :size="16" />
                        </a>
                    @endcan
                </x-slot:actions>

                <div class="fnc-cats">
                    @foreach ($kpis as $kpi)
                        <x-fnc.cat-card
                            :label="$kpi['label']"
                            :value="$kpi['value']"
                            :meta="$kpi['meta']"
                            :icon="$kpi['icon']"
                            :tone="$kpi['tone']"
                            :url="$kpi['url']" />
                    @endforeach
                </div>
            </x-fnc.panel>
        @endif

        {{-- Dona: composición por categoría (equivale a "Projects Overview") --}}
        @can('equipos.ver')
            <x-fnc.panel
                title="Por categoría"
                sub="Activos por tipo de recurso"
                icon="chart-pie"
                class="fnc-board__donut">
                @if ($totalInventario > 0 && $topCategorias !== [])
                    <x-fnc.donut
                        :segments="$topCategorias"
                        :total="$totalInventario"
                        center-label="Activos" />
                @else
                    <x-fnc.empty tone="slate" icon="package" title="Sin activos registrados" />
                @endif
            </x-fnc.panel>
        @endcan

        {{-- Serie temporal (equivale a "Income VS Expense") --}}
        @can('mesaayuda.ver')
            <x-fnc.panel
                title="Tickets de soporte"
                sub="Solicitudes de los últimos 6 meses"
                icon="headset"
                class="fnc-board__chart">
                <x-slot:actions>
                    <a class="fnc-icon-btn" href="{{ route('tickets.index') }}" title="Ver bandeja" aria-label="Ver bandeja">
                        <x-fnc.icon name="arrow-up-right" :size="16" />
                    </a>
                </x-slot:actions>

                <x-fnc.chart
                    :points="$serieTickets"
                    tone="info"
                    unit=" tickets"
                    aria-label="Tickets creados por mes" />

                <div class="fnc-row fnc-row--wrap" style="margin-top: 14px; gap: 18px;">
                    <div>
                        <div class="fnc-muted" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">Abiertos</div>
                        <div class="fnc-num" style="font-size:1.2rem; font-weight:800;">{{ number_format((int) $tickets['abiertos']) }}</div>
                    </div>
                    <div>
                        <div class="fnc-muted" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">Críticos</div>
                        <div class="fnc-num" style="font-size:1.2rem; font-weight:800; color: {{ (int) $tickets['criticos'] > 0 ? 'var(--fnc-danger)' : 'inherit' }};">
                            {{ number_format((int) $tickets['criticos']) }}
                        </div>
                    </div>
                    <div>
                        <div class="fnc-muted" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">
                            MTTR ({{ $tickets['diasMttr'] }} d)
                        </div>
                        <div class="fnc-num" style="font-size:1.2rem; font-weight:800;">
                            @if ($tickets['mttrHoras'] !== null)
                                {{ $tickets['mttrHoras'] }} h
                            @else
                                <span class="fnc-muted" style="font-size:.95rem;">Sin cierres</span>
                            @endif
                        </div>
                    </div>
                </div>
            </x-fnc.panel>
        @endcan

        {{-- Barras de progreso (equivale a "Invoice Overview") --}}
        @can('equipos.ver')
            <x-fnc.panel
                title="Disponibilidad del parque"
                sub="Composición por estado operativo"
                icon="layers"
                class="fnc-board__bars">
                <x-fnc.bar-list :rows="$filasEstado" :show-pct="true" class="fnc-bars--progress" />
            </x-fnc.panel>
        @endcan

        {{-- Próximos vencimientos (equivale a "My Meetings") --}}
        <x-fnc.panel
            title="Próximos vencimientos"
            sub="Licencias, garantías y devoluciones"
            icon="calendar-clock"
            class="fnc-board__venc">
            <x-slot:actions>
                @can('licencias.ver')
                    <a class="fnc-icon-btn" href="{{ route('licencias.index') }}" title="Ver licencias" aria-label="Ver licencias">
                        <x-fnc.icon name="hourglass" :size="16" />
                    </a>
                @endcan
            </x-slot:actions>

            @if ($vencimientos->isNotEmpty())
                <div class="fnc-events">
                    @foreach ($vencimientos as $evento)
                        <x-fnc.event-row
                            :title="$evento['title']"
                            :day="$evento['day']"
                            :kicker="$evento['kicker']"
                            :meta="$evento['meta']"
                            :tone="$evento['tone']"
                            :url="$evento['url']" />
                    @endforeach
                </div>
            @else
                <x-fnc.empty
                    icon="circle-check"
                    title="Sin vencimientos próximos"
                    text="Nada crítico en la ventana seleccionada." />
            @endif
        </x-fnc.panel>

        {{-- Tickets abiertos (equivale a "Open Tickets") --}}
        @can('mesaayuda.ver')
            <x-fnc.panel
                title="Tickets abiertos"
                sub="Prioridad alta o crítica sin resolver"
                icon="siren"
                class="fnc-board__tickets">
                <x-slot:actions>
                    <a class="fnc-icon-btn" href="{{ route('tickets.index', ['estado' => 'Abierto'], false) }}" title="Ver bandeja" aria-label="Ver bandeja">
                        <x-fnc.icon name="arrow-up-right" :size="16" />
                    </a>
                </x-slot:actions>

                @if ($tickets['cola']->isNotEmpty())
                    <div class="fnc-tickets">
                        @foreach ($tickets['cola'] as $ticket)
                            @php
                                $tono = match ($ticket->prioridad) {
                                    'Crítica' => 'danger',
                                    'Alta' => 'warning',
                                    'Media' => 'info',
                                    default => 'slate',
                                };
                                $nombre = trim(($ticket->funcionario?->nombres ?? '') . ' ' . ($ticket->funcionario?->apellidos ?? ''));
                            @endphp
                            <x-fnc.ticket-row
                                :name="$nombre ?: 'Sin asignar'"
                                :text="$ticket->titulo"
                                :tone="$tono"
                                :url="route('tickets.show', $ticket, false)" />
                        @endforeach
                    </div>
                @else
                    <x-fnc.empty
                        icon="circle-check"
                        title="Sin tickets prioritarios"
                        text="No hay solicitudes de prioridad alta o crítica pendientes." />
                @endif
            </x-fnc.panel>
        @endcan
    </div>

    {{-- ==================== Resto de paneles del tablero ================== --}}
    <div class="fnc-dash__rest">

        @can('licencias.ver')
            <x-fnc.panel
                title="Licencias"
                sub="Vencimientos y uso de cupos ({{ $ventana }} días)"
                icon="key-round">
                <x-slot:actions>
                    <a class="fnc-link" href="{{ route('licencias.index') }}">
                        Ver licencias
                        <x-fnc.icon name="arrow-up-right" :size="14" />
                    </a>
                </x-slot:actions>

                <div class="fnc-row" style="gap:10px; margin-bottom:14px;">
                    <span class="fnc-pill fnc-tone-{{ (int) $licencias['vencidas'] > 0 ? 'danger' : 'success' }}">
                        {{ number_format((int) $licencias['vencidas']) }} vencidas
                    </span>
                    <span class="fnc-pill fnc-tone-{{ (int) $licencias['porVencer'] > 0 ? 'warning' : 'slate' }}">
                        {{ number_format((int) $licencias['porVencer']) }} por vencer
                    </span>
                </div>

                @if ($licencias['cumplimiento'] !== [])
                    <x-fnc.bar-list :rows="$licencias['cumplimiento']" class="fnc-bars--progress" />
                @else
                    <x-fnc.empty tone="slate" icon="key-round" title="Sin cupos parametrizados" />
                @endif
            </x-fnc.panel>
        @endcan

        @can('equipos.ver')
            <x-fnc.panel
                title="Antigüedad del parque"
                sub="A partir de la fecha de compra"
                icon="hourglass">
                <x-fnc.bar-list :rows="$inventario['antiguedad']" :show-pct="true" class="fnc-bars--progress" />
            </x-fnc.panel>
        @endcan

        @can('equipos.ver')
            <x-fnc.panel
                title="Responsabilidades por vencer"
                sub="Actas vigentes que cumplen fecha en {{ $responsabilidades['diasAviso'] }} días"
                icon="clipboard-check">
                <x-slot:actions>
                    <a class="fnc-link" href="{{ route('equipos.index', ['estado' => 'responsabilidad'], false) }}">
                        Ver equipos
                        <x-fnc.icon name="arrow-up-right" :size="14" />
                    </a>
                </x-slot:actions>

                @if ($responsabilidades['cola']->isNotEmpty())
                    <ul class="fnc-list">
                        @foreach ($responsabilidades['cola'] as $asignacion)
                            @php
                                $fecha = $asignacion->fecha_final_estimada;
                                $dias = $fecha ? (int) $hoy->diffInDays($fecha->copy()->startOfDay(), false) : null;
                                $vencida = $dias !== null && $dias < 0;
                            @endphp
                            <x-fnc.queue-item
                                icon="clipboard-list"
                                tone="{{ $vencida ? 'danger' : 'warning' }}"
                                :title="$asignacion->nombre_usuario ?: 'Responsabilidad #' . $asignacion->id"
                                :meta="collect([$asignacion->equipo?->nombre_equipo, $asignacion->area, $asignacion->cargo])->filter()->implode(' · ')"
                                :pill="$dias === null ? null : ($vencida ? 'Cumplida hace ' . abs($dias) . ' d' : 'En ' . $dias . ' d')"
                                :url="route('equipos.index', ['estado' => 'responsabilidad'], false)" />
                        @endforeach
                    </ul>
                @else
                    <x-fnc.empty
                        icon="circle-check"
                        title="Sin responsabilidades por vencer"
                        text="Ninguna acta de responsabilidad vigente cumple fecha próximamente." />
                @endif
            </x-fnc.panel>
        @endcan

        @can('equipos.ver')
            <x-fnc.panel
                title="Garantías por vencer"
                sub="Equipos cuya garantía expira en {{ $ventana }} días"
                icon="shield-check">
                @if ($garantias['cola']->isNotEmpty())
                    <ul class="fnc-list">
                        @foreach ($garantias['cola'] as $equipo)
                            @php
                                $vence = $equipo->fin_garantia ? \Illuminate\Support\Carbon::parse($equipo->fin_garantia) : null;
                                $dias = $vence ? (int) $hoy->diffInDays($vence->copy()->startOfDay(), false) : null;
                            @endphp
                            <x-fnc.queue-item
                                icon="shield"
                                tone="{{ $dias !== null && $dias <= 15 ? 'warning' : 'info' }}"
                                :title="$equipo->nombre_equipo ?: ($equipo->placa ?: 'Activo #' . $equipo->id)"
                                :meta="collect([$equipo->tipoRecurso?->nombre, $equipo->marca, $equipo->modelo])->filter()->implode(' · ')"
                                :pill="$dias === null ? null : ($dias <= 0 ? 'Vence hoy' : 'En ' . $dias . ' d')"
                                :url="route('equipos.show', $equipo, false)" />
                        @endforeach
                    </ul>
                @else
                    <x-fnc.empty
                        icon="shield-check"
                        title="Sin garantías en riesgo"
                        text="Ningún equipo vence su garantía dentro de la ventana seleccionada." />
                @endif
            </x-fnc.panel>
        @endcan

        @can('equipos.ver')
            <x-fnc.panel
                title="Préstamos por devolver"
                sub="Vencidos o con devolución en {{ $prestamos['diasAviso'] }} días"
                icon="arrow-right-left">
                <x-slot:actions>
                    <a class="fnc-link" href="{{ route('prestamos.index', ['estado' => 'Activo'], false) }}">
                        Ver préstamos
                        <x-fnc.icon name="arrow-up-right" :size="14" />
                    </a>
                </x-slot:actions>

                @if ($prestamos['cola']->isNotEmpty())
                    <ul class="fnc-list">
                        @foreach ($prestamos['cola'] as $prestamo)
                            @php
                                $fecha = $prestamo->fecha_devolucion_prevista;
                                $dias = $fecha ? (int) $hoy->diffInDays($fecha->copy()->startOfDay(), false) : null;
                                $atrasado = $dias !== null && $dias < 0;
                            @endphp
                            <x-fnc.queue-item
                                icon="clock"
                                tone="{{ $atrasado ? 'danger' : 'info' }}"
                                :title="$prestamo->persona_nombre ?: 'Préstamo #' . $prestamo->id"
                                :meta="collect([$prestamo->equipo?->nombre_equipo, $prestamo->equipo?->placa])->filter()->implode(' · ') ?: 'Equipo #' . $prestamo->equipo_id"
                                :pill="$dias === null ? null : ($atrasado ? 'Atrasado ' . abs($dias) . ' d' : ($dias === 0 ? 'Hoy' : 'En ' . $dias . ' d'))"
                                :value="$fecha ? $fecha->format('d/m/Y') : null"
                                :url="route('prestamos.index', ['estado' => 'Activo'], false)" />
                        @endforeach
                    </ul>
                @else
                    <x-fnc.empty
                        icon="circle-check"
                        title="Sin devoluciones pendientes"
                        text="Todos los préstamos vigentes están dentro del plazo previsto." />
                @endif
            </x-fnc.panel>
        @endcan
    </div>

    {{-- ==================== Últimos activos registrados ================== --}}
    @can('equipos.ver')
        @if ($ultimosEquipos->isNotEmpty())
            <x-fnc.panel title="Últimos activos registrados" icon="monitor" flush>
                <x-slot:actions>
                    <a class="fnc-link" href="{{ route('equipos.index') }}">
                        Ver inventario
                        <x-fnc.icon name="arrow-up-right" :size="14" />
                    </a>
                </x-slot:actions>

                <div class="fnc-table-wrap">
                    <table class="fnc-table">
                        <caption class="fnc-sr">Últimos activos registrados en el inventario</caption>
                        <thead>
                            <tr>
                                <th scope="col">Activo</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Serie / Placa</th>
                                <th scope="col">Asignado a</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Registro</th>
                                <th scope="col"><span class="fnc-sr">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ultimosEquipos as $equipo)
                                @php $asignado = $equipo->usuarioAsignado; @endphp
                                <tr>
                                    <td class="fnc-table__strong">
                                        {{ $equipo->nombre_equipo ?: 'Activo #' . $equipo->id }}
                                    </td>
                                    <td>{{ $equipo->tipoRecurso?->nombre ?? '—' }}</td>
                                    <td class="fnc-muted">{{ $equipo->serial ?: ($equipo->placa ?: '—') }}</td>
                                    <td>
                                        @if ($asignado)
                                            {{ $asignado->nombre ?: '—' }}
                                            @if ($asignado->cedula)
                                                <span class="fnc-muted">· {{ $asignado->cedula }}</span>
                                            @endif
                                        @else
                                            <span class="fnc-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-fnc.pill :tone="\App\Services\Dashboard\DashboardService::estadoTone($equipo->estado_operativo)" dot>
                                            {{ $equipo->estado_label }}
                                        </x-fnc.pill>
                                    </td>
                                    <td class="fnc-muted">{{ $equipo->created_at?->format('d/m/Y') }}</td>
                                    <td class="fnc-table__num">
                                        <a class="fnc-link" href="{{ route('equipos.show', $equipo, false) }}">
                                            Ver ficha
                                            <x-fnc.icon name="chevron-right" :size="13" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-fnc.panel>
        @endif
    @endcan

    <p class="fnc-dash__stamp">
        <x-fnc.icon name="clock" :size="13" />
        Datos calculados el {{ $generadoEn->format('d/m/Y') }} a las {{ $generadoEn->format('H:i') }}
        · ventana de vencimientos de {{ $ventana }} días.
    </p>
</div>
@endsection
