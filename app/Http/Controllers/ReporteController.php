<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ActivosPorFuncionarioExport;
use App\Exports\AsignacionesExport;
use App\Exports\MantenimientosExport;
use App\Exports\GarantiasExport;
use App\Models\Equipo;
use App\Models\HistorialTecnico;
use App\Models\Asignacion;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index()
    {
        $camposExportables = \App\Models\CampoPersonalizado::where('modulo', 'equipos')
            ->where('exportable', 1)
            ->select('id', 'nombre', 'exportar_por_defecto')
            ->orderBy('orden')
            ->get();

        $plantillasExportacion = \App\Models\PlantillaExportacion::where('modulo', 'equipos')
            ->select('id', 'nombre', 'configuracion_json')
            ->orderBy('nombre')
            ->get();

        $columnasAdicionalesCmdb = \App\Exports\EquiposExport::columnasAdicionalesSobreCmdbPrincipal();

        return view('reportes.index', compact('camposExportables', 'plantillasExportacion', 'columnasAdicionalesCmdb'));
    }

    public function activosPorFuncionario()
    {
        return Excel::download(new ActivosPorFuncionarioExport, 'Activos_Por_Funcionario_' . date('Ymd_His') . '.xlsx');
    }

    public function asignaciones()
    {
        return Excel::download(new AsignacionesExport, 'Asignaciones_Y_Movimientos_' . date('Ymd_His') . '.xlsx');
    }

    public function mantenimientos()
    {
        return Excel::download(new MantenimientosExport, 'Mantenimientos_' . date('Ymd_His') . '.xlsx');
    }

    public function garantias()
    {
        return Excel::download(new GarantiasExport, 'Garantias_' . date('Ymd_His') . '.xlsx');
    }

    public function estadisticasPdf()
    {
        $stats = [
            'total_equipos' => Equipo::count(),
            'equipos_activos' => Equipo::where('estado_operativo', 'activo')->count(),
            'equipos_inactivos' => Equipo::whereIn('estado_operativo', ['baja'])->count(),
            'equipos_mantenimiento' => Equipo::where('estado_operativo', 'mantenimiento')->count(),
            'equipos_baja' => Equipo::where('estado_operativo', 'baja')->count(),
            'equipos_por_tipo' => Equipo::join('tipo_recursos', 'equipos.tipo_recurso_id', '=', 'tipo_recursos.id')
                ->selectRaw('tipo_recursos.nombre, count(equipos.id) as total')
                ->groupBy('tipo_recursos.nombre')
                ->get(),
            'total_mantenimientos' => HistorialTecnico::count(),
            'mantenimientos_recientes' => HistorialTecnico::with('equipo')->latest('fecha_evento')->take(5)->get(),
            'total_asignaciones' => Asignacion::count(),
            'ultimas_asignaciones' => Asignacion::with('equipo')->latest('fecha_accion')->take(5)->get(),
        ];

        $pdf = Pdf::loadView('pdf.estadisticas', compact('stats'))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->stream('Estadisticas_Inventario_' . date('Ymd_His') . '.pdf');
    }
}
