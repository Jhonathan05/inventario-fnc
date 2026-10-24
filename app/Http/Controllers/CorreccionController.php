<?php

namespace App\Http\Controllers;

use App\Models\Correccion;
use App\Services\CorreccionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pestaña "Correcciones" del módulo Importar.
 *
 * La importación del CMDB es NO condicionada: nunca bloquea las filas con
 * datos incompletos; cada faltante queda aquí como pendiente corregible
 * (o justificable) directamente desde la vista.
 */
class CorreccionController extends Controller
{
    public function __construct(private CorreccionService $correcciones) {}

    public function index(Request $request): View
    {
        $query = Correccion::with(['equipo:id,nombre_equipo,serial,placa,marca,modelo,estado_operativo,tipo_recurso_id', 'equipo.tipoRecurso:id,nombre'])
            ->select('correcciones.*');

        // Filtro de estado (por defecto: pendientes)
        $estado = $request->input('estado', 'pendiente');
        if (in_array($estado, ['pendiente', 'resuelta', 'ignorada', 'todas'], true)) {
            if ($estado !== 'todas') $query->where('correcciones.estado', $estado);
        }

        if ($request->filled('severidad') && in_array($request->severidad, ['alta', 'media', 'baja'], true)) {
            $query->where('correcciones.severidad', $request->severidad);
        }

        if ($request->filled('seccion') && in_array($request->seccion, ['identidad', 'ficha', 'custodia', 'catalogos'], true)) {
            $query->where('correcciones.seccion', $request->seccion);
        }

        if ($request->filled('q')) {
            $q = mb_strtolower(trim((string) $request->q));
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('LOWER(correcciones.campo) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(correcciones.etiqueta) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(COALESCE(correcciones.valor_actual, \'\')) LIKE ?', ["%{$q}%"])
                    ->orWhereHas('equipo', function ($e) use ($q) {
                        $e->whereRaw('LOWER(COALESCE(nombre_equipo, \'\')) LIKE ?', ["%{$q}%"])
                          ->orWhereRaw('LOWER(COALESCE(serial, \'\')) LIKE ?', ["%{$q}%"])
                          ->orWhereRaw('LOWER(COALESCE(placa, \'\')) LIKE ?', ["%{$q}%"]);
                    });
            });
        }

        // Alta primero, luego las más antiguas.
        $ordenSeveridad = "CASE correcciones.severidad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END";
        $correcciones = $query->orderByRaw($ordenSeveridad)
            ->orderBy('correcciones.created_at')
            ->orderBy('correcciones.id')
            ->paginate(25)
            ->withQueryString();

        // Estadísticas para el semáforo de uniformidad.
        $stats = [
            'pendiente' => Correccion::where('estado', 'pendiente')->count(),
            'resuelta'  => Correccion::where('estado', 'resuelta')->count(),
            'ignorada'  => Correccion::where('estado', 'ignorada')->count(),
            'alta'      => Correccion::where('estado', 'pendiente')->where('severidad', 'alta')->count(),
        ];
        $stats['total'] = array_sum([$stats['pendiente'], $stats['resuelta'], $stats['ignorada']]);
        $stats['avance'] = $stats['total'] > 0
            ? (int) round((($stats['resuelta'] + $stats['ignorada']) / $stats['total']) * 100)
            : 100;

        return view('correcciones.index', [
            'correcciones' => $correcciones,
            'stats'         => $stats,
            'filtros'       => [
                'estado'   => $estado,
                'severidad'=> $request->input('severidad'),
                'seccion'  => $request->input('seccion'),
                'q'        => $request->input('q'),
            ],
        ]);
    }

    /** Opciones del catálogo para el select de una corrección (JSON). */
    public function opciones(Correccion $correccion): JsonResponse
    {
        $opciones = $this->correcciones->opcionesPara($correccion->catalogo);

        return response()->json(['opciones' => $opciones->values()]);
    }

    /** Aplica la corrección escrita por el analista. */
    public function resolver(Request $request, Correccion $correccion): RedirectResponse
    {
        $request->validate(['valor' => ['required', 'string', 'max:500']]);

        try {
            $this->correcciones->resolver($correccion, (string) $request->valor, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['valor' => $e->getMessage()]);
        }

        return back()->with('success', "Corregido «{$correccion->etiqueta}» en {$correccion->equipo?->nombre_equipo}.");
    }

    /** Ignora el pendiente con justificación (N/A justificado). */
    public function ignorar(Request $request, Correccion $correccion): RedirectResponse
    {
        $request->validate(['justificacion' => ['required', 'string', 'min:5', 'max:500']]);

        try {
            $this->correcciones->ignorar($correccion, (string) $request->justificacion, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['justificacion' => $e->getMessage()]);
        }

        return back()->with('success', 'Pendiente justificado y archivado.');
    }

    /** Re-escanea todos los activos y regenera los pendientes. */
    public function regenerar(): RedirectResponse
    {
        $nuevos = $this->correcciones->regenerarTodo('escaneo');

        return back()->with('success', "Escaneo completo: {$nuevos} pendiente(s) nuevo(s) detectado(s).");
    }
}
