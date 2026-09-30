<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\TipoRecurso;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalEquipos     = Equipo::count();
        $activos          = Equipo::where('estado_operativo', 'activo')->count();
        $disponibles      = Equipo::where('estado_operativo', 'disponible')->count();
        $enMantenimiento  = Equipo::where('estado_operativo', 'mantenimiento')->count();
        $deBaja           = Equipo::where('estado_operativo', 'baja')->count();

        $equiposPorTipo   = TipoRecurso::withCount('equipos')
            ->orderByDesc('equipos_count')
            ->get();

        $ultimosEquipos   = Equipo::select(['id', 'tipo_recurso_id', 'nombre_equipo', 'serial', 'marca', 'modelo', 'estado_operativo', 'created_at'])
            ->with([
                'tipoRecurso:id,nombre',
                'usuarioAsignado:id,equipo_id,nombre,cedula'
            ])
            ->latest()
            ->limit(5)
            ->get();

        $hoy = now();
        $alertasRojas = \App\Models\Licencia::where('estado', 'Vencida')
            ->orWhere('fecha_vencimiento', '<', $hoy->toDateString())
            ->count();
            
        $alertasAmarillas = \App\Models\Licencia::where('fecha_vencimiento', '>=', $hoy->toDateString())
            ->where('fecha_vencimiento', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where('estado', 'Activa')
            ->count();

        // Asignaciones Bajo Responsabilidad
        $respActivas = \App\Models\AsignacionResponsabilidad::where('estado', 'activa')->count();
        $respFinalizadas = \App\Models\AsignacionResponsabilidad::where('estado', 'finalizada')->count();
        $respPorVencer = \App\Models\AsignacionResponsabilidad::where('estado', 'activa')
            ->whereNotNull('fecha_final_estimada')
            ->where('fecha_final_estimada', '<=', $hoy->copy()->addDays(15)->toDateString())
            ->count();

        $respPorProyecto = \App\Models\AsignacionResponsabilidad::where('estado', 'activa')
            ->whereNotNull('proyecto')
            ->where('proyecto', '!=', '')
            ->select('proyecto', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('proyecto')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $respPorResponsable = \App\Models\Equipo::whereHas('asignacionResponsabilidadActiva')
            ->select('responsable_nombre', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereNotNull('responsable_nombre')
            ->where('responsable_nombre', '!=', '')
            ->groupBy('responsable_nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Préstamos
        $prestamosTotal = \App\Models\Prestamo::count();
        $prestamosActivos = \App\Models\Prestamo::whereIn('estado', ['Pendiente', 'Activo'])->count();
        $prestamosVencidos = \App\Models\Prestamo::where('estado', 'Vencido')->count();
        $prestamosDevueltos = \App\Models\Prestamo::where('estado', 'Devuelto')->count();
        $prestamosCancelados = \App\Models\Prestamo::where('estado', 'Cancelado')->count();
        $prestamosProximosVencer = \App\Models\Prestamo::whereIn('estado', ['Pendiente', 'Activo'])
            ->where('fecha_devolucion_prevista', '>', $hoy)
            ->where('fecha_devolucion_prevista', '<=', $hoy->copy()->addDays(2))
            ->get(); // Traer la colección para mostrar la alerta

        return view('dashboard', compact(
            'totalEquipos',
            'activos',
            'disponibles',
            'enMantenimiento',
            'deBaja',
            'equiposPorTipo',
            'ultimosEquipos',
            'alertasRojas',
            'alertasAmarillas',
            'respActivas',
            'respFinalizadas',
            'respPorVencer',
            'respPorProyecto',
            'respPorResponsable',
            'prestamosTotal',
            'prestamosActivos',
            'prestamosVencidos',
            'prestamosDevueltos',
            'prestamosCancelados',
            'prestamosProximosVencer'
        ));
    }
}
