@extends('layouts.inventario')

@section('title', 'Inicio')

@section('content')

{{-- Alertas de Licencias --}}
@if(isset($alertasRojas) && $alertasRojas > 0 && !session('licencias_vencidas_alert_shown'))
@php session()->put('licencias_vencidas_alert_shown', true); @endphp
<div class="alert alert-danger d-flex align-items-center mb-4 licencia-alerta" role="alert">
    <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
    <div>
        <strong>¡Atención!</strong> Hay {{ $alertasRojas }} licencia(s) vencida(s). <a href="{{ route('licencias.index', ['estado' => 'Vencida']) }}" class="alert-link">Ver licencias</a>.
    </div>
</div>
@endif

@if(isset($alertasAmarillas) && $alertasAmarillas > 0 && !session('licencias_por_vencer_alert_shown'))
@php session()->put('licencias_por_vencer_alert_shown', true); @endphp
<div class="alert alert-warning d-flex align-items-center mb-4 licencia-alerta" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
    <div>
        <strong>Aviso:</strong> Hay {{ $alertasAmarillas }} licencia(s) por vencer en los próximos 30 días. <a href="{{ route('licencias.index') }}" class="alert-link">Revisar licencias</a>.
    </div>
</div>
@endif

{{-- Alertas de Préstamos --}}
@if(isset($prestamosProximosVencer) && $prestamosProximosVencer->isNotEmpty() && !session('prestamos_por_vencer_alert_shown'))
@php session()->put('prestamos_por_vencer_alert_shown', true); @endphp
<div class="alert alert-warning d-flex align-items-center mb-4 licencia-alerta" role="alert">
    <i class="bi bi-calendar-x-fill fs-4 me-3 text-warning"></i>
    <div>
        <strong>Aviso de Préstamos:</strong> Hay {{ $prestamosProximosVencer->count() }} préstamo(s) temporal(es) por vencer próximamente. <a href="{{ route('prestamos.index') }}" class="alert-link">Revisar préstamos</a>.
    </div>
</div>
@endif

{{-- Resumen General del Inventario --}}
<div class="dashboard-summary-panel mb-4">
    <div class="dashboard-summary-header">
        <h2 class="dashboard-summary-title">
            <i class="bi bi-graph-up"></i> RESUMEN GENERAL DEL INVENTARIO
        </h2>
        <p class="dashboard-summary-subtitle">Estado actual del inventario tecnológico</p>
    </div>
    
    <div class="dashboard-summary-divider"></div>
    

    
    <div class="dashboard-summary-states">
        {{-- Total Equipos como una tarjeta normal, destacada --}}
        <div class="dashboard-state-item" style="border: 2px solid #0d6efd; background-color: #f8fbff; cursor: pointer;" onclick="mostrarEstadisticas('total', 'Total Registrados', {{ $totalEquipos }})">
            <div class="dashboard-state-icon text-primary" style="background-color: transparent;">
                <i class="bi bi-laptop fs-4"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label text-primary fw-bold text-uppercase" style="font-size: 0.8rem;">Total Registrados</div>
                <div class="dashboard-state-value text-primary fw-bolder fs-4">{{ $totalEquipos }}</div>
            </div>
        </div>
        <div class="dashboard-state-item" style="cursor: pointer;" onclick="mostrarEstadisticas('activo', 'Activos', {{ $activos }})">
            <div class="dashboard-state-icon active">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label">Activos</div>
                <div class="dashboard-state-value active">{{ $activos }}</div>
            </div>
        </div>

        <div class="dashboard-state-item" style="cursor: pointer;" onclick="mostrarEstadisticas('disponible', 'Disponibles', {{ $disponibles }})">
            <div class="dashboard-state-icon active">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label">Disponibles</div>
                <div class="dashboard-state-value active">{{ $disponibles }}</div>
            </div>
        </div>


        
        <div class="dashboard-state-item" style="cursor: pointer;" onclick="mostrarEstadisticas('mantenimiento', 'En Mantenimiento', {{ $enMantenimiento }})">
            <div class="dashboard-state-icon maintenance">
                <i class="bi bi-tools"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label">En mantenimiento</div>
                <div class="dashboard-state-value maintenance">{{ $enMantenimiento }}</div>
            </div>
        </div>
        
        <div class="dashboard-state-item" style="cursor: pointer;" onclick="mostrarEstadisticas('baja', 'Dados de baja', {{ $deBaja }})">
            <div class="dashboard-state-icon inactive">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label">Dados de baja</div>
                <div class="dashboard-state-value inactive">{{ $deBaja }}</div>
            </div>
        </div>

        {{-- Cajas Consolidadas (Agregadas por solicitud) --}}
        @if(isset($respActivas))
        <div class="dashboard-state-item" style="border: 1px solid #17a2b8; cursor: pointer;" onclick="mostrarEstadisticas('asignacion', 'Asignaciones', {{ $respActivas }})">
            <div class="dashboard-state-icon text-info" style="background-color: rgba(23, 162, 184, 0.1);">
                <i class="bi bi-person-badge fs-4"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label text-info fw-bold text-uppercase" style="font-size: 0.8rem;">Asignaciones</div>
                <div class="dashboard-state-value text-info fw-bolder fs-4">{{ $respActivas }}</div>
            </div>
        </div>
        @endif

        @if(isset($prestamosTotal))
        <div class="dashboard-state-item" style="border: 1px solid #6f42c1; cursor: pointer;" onclick="mostrarEstadisticas('prestamo', 'Préstamos', {{ $prestamosTotal }})">
            <div class="dashboard-state-icon" style="color: #6f42c1; background-color: rgba(111, 66, 193, 0.1);">
                <i class="bi bi-calendar2-range fs-4"></i>
            </div>
            <div class="dashboard-state-content">
                <div class="dashboard-state-label fw-bold text-uppercase" style="color: #6f42c1; font-size: 0.8rem;">Préstamos</div>
                <div class="dashboard-state-value fw-bolder fs-4" style="color: #6f42c1;">{{ $prestamosTotal }}</div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="pb-4">
    {{-- Panel Dinámico de Equipos por Tipo --}}
    <x-ui.card noPadding="false" class="h-100 shadow-sm border-0 mb-4" style="margin-bottom: 2rem !important;">
        <x-slot name="header">
            <div class="bg-white fw-semibold border-0 pt-4 px-4 fs-5" id="dynamic-stats-title">
                <i class="bi bi-pie-chart me-2 text-primary"></i>Activos por Tipo
            </div>
        </x-slot>
        <div id="dynamic-stats-container" class="px-3 pb-4 pt-2" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 1.5rem;">
            <!-- Javascript inyectará las barras aquí -->
        </div>
    </x-ui.card>
</div>

@php
    // Obtener las estadísticas directamente en la vista para no tocar los controladores (backend)
    $stats = \App\Models\Equipo::select('estado_operativo', 'tipo_recurso_id', \DB::raw('count(*) as count'))
        ->with('tipoRecurso:id,nombre')
        ->groupBy('estado_operativo', 'tipo_recurso_id')
        ->get();
    
    $datosPorEstado = [
        'total' => $equiposPorTipo->map(fn($t) => ['nombre' => $t->nombre, 'count' => $t->equipos_count])->values()->toArray(),
        'activo' => [], 'disponible' => [], 'mantenimiento' => [], 'baja' => [],
        'asignacion' => [], 'prestamo' => []
    ];
    
    foreach($stats as $stat) {
        if(isset($datosPorEstado[$stat->estado_operativo])) {
            $datosPorEstado[$stat->estado_operativo][] = [
                'nombre' => $stat->tipoRecurso ? $stat->tipoRecurso->nombre : 'Sin Clasificar',
                'count' => $stat->count
            ];
        }
    }

    // Estadísticas para Asignaciones (Activas)
    $asignacionesStats = \App\Models\AsignacionResponsabilidad::join('equipos', 'asignaciones_responsabilidad.equipo_id', '=', 'equipos.id')
        ->leftJoin('tipo_recursos', 'equipos.tipo_recurso_id', '=', 'tipo_recursos.id')
        ->where('asignaciones_responsabilidad.estado', 'activa')
        ->select('tipo_recursos.nombre', \DB::raw('count(*) as count'))
        ->groupBy('tipo_recursos.nombre')
        ->get();

    foreach($asignacionesStats as $stat) {
        $datosPorEstado['asignacion'][] = [
            'nombre' => $stat->nombre ?: 'Sin Clasificar',
            'count' => $stat->count
        ];
    }

    // Estadísticas para Préstamos
    $prestamosStats = \App\Models\Prestamo::join('equipos', 'prestamos.equipo_id', '=', 'equipos.id')
        ->leftJoin('tipo_recursos', 'equipos.tipo_recurso_id', '=', 'tipo_recursos.id')
        ->select('tipo_recursos.nombre', \DB::raw('count(*) as count'))
        ->groupBy('tipo_recursos.nombre')
        ->get();

    foreach($prestamosStats as $stat) {
        $datosPorEstado['prestamo'][] = [
            'nombre' => $stat->nombre ?: 'Sin Clasificar',
            'count' => $stat->count
        ];
    }
    
    foreach($datosPorEstado as $key => &$arr) {
        usort($arr, fn($a, $b) => $b['count'] <=> $a['count']);
    }
@endphp
@endsection

@push('scripts')
<script>
    const estadisticas = @json($datosPorEstado);

    function mostrarEstadisticas(estado, titulo, total) {
        // --- Barras de Tipos (Izquierda) ---
        const data = estadisticas[estado] || [];
        const container = document.getElementById('dynamic-stats-container');
        const titleEl = document.getElementById('dynamic-stats-title');
        titleEl.innerHTML = `<i class="bi bi-pie-chart me-2 text-primary"></i> Activos por Tipo: <span class="fw-bold">${titulo}</span>`;
        
        // Efecto visual al hacer clic en las tarjetas (verificando evento)
        if(typeof event !== 'undefined' && event && event.currentTarget && event.currentTarget.classList) {
            document.querySelectorAll('.dashboard-state-item').forEach(el => el.classList.remove('shadow'));
            event.currentTarget.classList.add('shadow');
        }

        if (data.length === 0) {
            container.innerHTML = `<div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                No hay equipos registrados en este estado.
            </div>`;
        } else {
            let html = '';
            data.forEach(item => {
                const porcentaje = total > 0 ? (item.count / total * 100) : 0;
                html += `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">${item.nombre}</span>
                            <span class="badge bg-primary rounded-pill">${item.count}</span>
                        </div>
                        <div class="progress dashboard-progress-bar" style="height: 8px;">
                            <div class="progress-bar rounded-pill" style="width: ${porcentaje}%"></div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
        

    }

    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar mostrando el total general, seleccionando visualmente el total
        const totalCard = document.querySelector('.dashboard-state-item');
        if(totalCard) totalCard.classList.add('shadow');
        mostrarEstadisticas('total', 'Total Registrados', {{ $totalEquipos }});
    });

    setTimeout(function() {
        const alertas = document.querySelectorAll('.licencia-alerta');
        alertas.forEach(alerta => {
            alerta.style.transition = "opacity 0.5s ease";
            alerta.style.opacity = "0";
            setTimeout(() => alerta.remove(), 500);
        });
    }, 10000);
</script>
@endpush

