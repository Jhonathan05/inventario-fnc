@extends('layouts.inventario')

@section('title', 'Panel de control')

@section('content')
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

    // Ventana de vencimientos: control segmentado.
    $opcionesVentana = collect($ventanas ?? [30])->map(fn ($dias) => [
        'value' => $dias,
        'label' => $dias . ' días',
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
@endphp

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
            <x-fnc.segmented
                :items="$opcionesVentana"
                :current="$ventana"
                param="ventana"
                aria-label="Ventana de vencimientos" />

            @can('dashboard.ver')
                <a class="fnc-btn" href="{{ route('reportes.index') }}">
                    <x-fnc.icon name="chart-column" :size="15" />
                    Reportes
                </a>
            @endcan

            <a class="fnc-btn fnc-btn--primary" href="{{ route('dashboard') }}">
                <x-fnc.icon name="refresh-cw" :size="15" />
                Actualizar
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

    {{-- ============================== KPIs =============================== --}}
    @if (! empty($kpis))
        <section class="fnc-grid fnc-grid--kpis" aria-label="Indicadores principales">
            @foreach ($kpis as $kpi)
                <x-fnc.kpi
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :meta="$kpi['meta']"
                    :icon="$kpi['icon']"
                    :tone="$kpi['tone']"
                    :url="$kpi['url']"
                    :spark="$kpi['key'] === 'tickets_abiertos' ? $serieTickets : null" />
            @endforeach
        </section>
    @endif

    {{-- ================== Analítica: tickets y estado ==================== --}}
    <div class="fnc-grid fnc-grid--sidebar">
        <div class="fnc-stack">
            @can('mesaayuda.ver')
                <x-fnc.panel
                    title="Tickets de soporte"
                    sub="Solicitudes registradas en los últimos 6 meses"
                    icon="headset">
                    <x-slot:actions>
                        <a class="fnc-link" href="{{ route('tickets.index') }}">
                            Ver bandeja
                            <x-fnc.icon name="arrow-up-right" :size="14" />
                        </a>
                    </x-slot:actions>

                    <x-fnc.chart
                        :points="$serieTickets"
                        tone="brand"
                        unit=" tickets"
                        aria-label="Tickets creados por mes" />

                    <div class="fnc-row fnc-row--wrap" style="margin-top: 14px; gap: 18px;">
                        <div>
                            <div class="fnc-muted" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">Abiertos</div>
                            <div class="fnc-num" style="font-size:1.35rem; font-weight:700;">{{ number_format((int) $tickets['abiertos']) }}</div>
                        </div>
                        <div>
                            <div class="fnc-muted" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">Críticos</div>
                            <div class="fnc-num" style="font-size:1.35rem; font-weight:700; color: {{ (int) $tickets['criticos'] > 0 ? 'var(--fnc-danger)' : 'inherit' }};">
                                {{ number_format((int) $tickets['criticos']) }}
                            </div>
                        </div>
                        <div>
                            <div class="fnc-muted" style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em;">
                                MTTR ({{ $tickets['diasMttr'] }} d)
                            </div>
                            <div class="fnc-num" style="font-size:1.35rem; font-weight:700;">
                                @if ($tickets['mttrHoras'] !== null)
                                    {{ $tickets['mttrHoras'] }} h
                                @else
                                    <span class="fnc-muted" style="font-size:1rem;">Sin cierres</span>
                                @endif
                            </div>
                        </div>

                        @php
                            $criticos = (int) ($tickets['porPrioridad']['Crítica'] ?? 0);
                            $altos = (int) ($tickets['porPrioridad']['Alta'] ?? 0);
                        @endphp
                        <div class="fnc-spacer"></div>
                        <div class="fnc-row" style="gap:6px;">
                            <span class="fnc-pill fnc-tone-danger">{{ number_format($criticos) }} crítica</span>
                            <span class="fnc-pill fnc-tone-warning">{{ number_format($altos) }} alta</span>
                        </div>
                    </div>
                </x-fnc.panel>
            @endcan

            @can('equipos.ver')
                <x-fnc.panel title="Disponibilidad del parque" icon="layers" sub="Composición por estado operativo">
                    <x-fnc.bar-list :rows="$filasEstado" :show-pct="true" />
                </x-fnc.panel>
            @endcan
        </div>

        <div class="fnc-stack">
            @can('equipos.ver')
                <x-fnc.panel title="Por categoría" icon="chart-pie" sub="Activos por tipo de recurso">
                    @if ($totalInventario > 0 && $topCategorias !== [])
                        <x-fnc.donut
                            :segments="$topCategorias"
                            :total="$totalInventario"
                            center-label="Activos" />
                    @else
                        <x-fnc.empty tone="slate" icon="package" title="Sin activos registrados" />
                    @endif
                </x-fnc.panel>

                <x-fnc.panel title="Antigüedad del parque" icon="hourglass" sub="A partir de la fecha de compra">
                    <x-fnc.bar-list :rows="$inventario['antiguedad']" :show-pct="true" />
                </x-fnc.panel>
            @endcan
        </div>
    </div>

    {{-- ================= Licencias y garantías ========================== --}}
    <div class="fnc-grid fnc-grid--halves">
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
                    <x-fnc.bar-list :rows="$licencias['cumplimiento']" />
                @else
                    <x-fnc.empty tone="slate" icon="key-round" title="Sin cupos parametrizados" />
                @endif

                @if ($licencias['criticas']->isNotEmpty())
                    <hr class="fnc-hr">
                    <ul class="fnc-list">
                        @foreach ($licencias['criticas'] as $licencia)
                            @php
                                $vence = $licencia->fecha_vencimiento ? \Illuminate\Support\Carbon::parse($licencia->fecha_vencimiento) : null;
                                $dias = $vence ? (int) $hoy->diffInDays($vence->copy()->startOfDay(), false) : null;
                            @endphp
                            <x-fnc.queue-item
                                icon="key"
                                tone="{{ $dias !== null && $dias < 0 ? 'danger' : 'warning' }}"
                                :title="$licencia->nombre"
                                :meta="$vence ? 'Vence el ' . $vence->format('d/m/Y') : 'Sin fecha de vencimiento'"
                                :pill="$dias === null ? null : ($dias < 0 ? 'Vencida' : 'En ' . $dias . ' d')"
                                :url="route('licencias.index', ['estado' => $licencia->estado === 'Vencida' ? 'Vencida' : 'Activa'], false)" />
                        @endforeach
                    </ul>
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
    </div>

    {{-- ====================== Cola operativa ============================= --}}
    <div class="fnc-dash__queues">
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

        @can('mesaayuda.ver')
            <x-fnc.panel
                title="Tickets prioritarios"
                sub="Prioridad alta o crítica sin resolver"
                icon="headset">
                <x-slot:actions>
                    <a class="fnc-link" href="{{ route('tickets.index', ['estado' => 'Abierto'], false) }}">
                        Ver bandeja
                        <x-fnc.icon name="arrow-up-right" :size="14" />
                    </a>
                </x-slot:actions>

                @if ($tickets['cola']->isNotEmpty())
                    <ul class="fnc-list">
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
                            <x-fnc.queue-item
                                icon="wrench"
                                :tone="$tono"
                                :title="$ticket->titulo"
                                :meta="collect([$nombre ?: null, $ticket->estado, $ticket->fecha_solicitud?->format('d/m/Y')])->filter()->implode(' · ')"
                                :pill="$ticket->prioridad"
                                :url="route('tickets.show', $ticket, false)" />
                        @endforeach
                    </ul>
                @else
                    <x-fnc.empty
                        icon="circle-check"
                        title="Sin tickets prioritarios"
                        text="No hay solicitudes de prioridad alta o crítica pendientes." />
                @endif
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
