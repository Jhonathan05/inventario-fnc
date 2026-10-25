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
 * datos incompletos; cada faltante queda como pendiente corregible (o
 * justificable) directamente desde la vista.
 *
 * Lote B5 — organización para miles de pendientes:
 *  - vista=campos (por defecto): frentes de trabajo agrupados por campo,
 *    con corrección/justificación EN LOTE y re-escaneo único por activo;
 *  - vista=activos: peores fichas primero (completar un activo de una vez);
 *  - vista=lista: el detalle fila a fila con filtros por campo y activo.
 */
class CorreccionController extends Controller
{
    public function __construct(private CorreccionService $correcciones) {}

    public function index(Request $request): View
    {
        $vista = in_array($request->input('vista'), ['campos', 'activos', 'lista'], true)
            ? $request->input('vista')
            : 'campos';

        // Estadísticas globales (semáforo de uniformidad)
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

        $datos = [
            'correcciones' => null,
            'porCampo'     => null,
            'porActivo'    => null,
            'stats'        => $stats,
            'filtros'      => [
                'vista'    => $vista,
                'estado'   => $request->input('estado', 'pendiente'),
                'severidad'=> $request->input('severidad'),
                'seccion'  => $request->input('seccion'),
                'campo'     => $request->input('campo'),
                'equipo_id'=> $request->input('equipo_id'),
                'q'        => $request->input('q'),
            ],
        ];

        if ($vista === 'campos') {
            $porCampo = $this->correcciones->resumenPorCampo();

            if ($request->filled('seccion')) {
                $porCampo = $porCampo->where('seccion', $request->seccion);
            }
            if ($request->filled('severidad')) {
                $porCampo = $porCampo->where('severidad', $request->severidad);
            }
            if ($request->filled('q')) {
                $q = mb_strtolower($request->q);
                $porCampo = $porCampo->filter(fn ($f) => str_contains(mb_strtolower($f->etiqueta . ' ' . $f->campo), $q));
            }

            $datos['porCampo'] = $porCampo->values();
        }

        if ($vista === 'activos') {
            $datos['porActivo'] = $this->correcciones->topActivos();
        }

        if ($vista === 'lista') {
            $query = Correccion::with(['equipo:id,nombre_equipo,serial,placa,estado_operativo,tipo_recurso_id', 'equipo.tipoRecurso:id,nombre'])
                ->select('correcciones.*');

            $estado = $request->input('estado', 'pendiente');
            if (in_array($estado, ['pendiente', 'resuelta', 'ignorada'], true)) {
                $query->where('correcciones.estado', $estado);
            }

            $query->when($request->filled('severidad') && in_array($request->severidad, ['alta', 'media', 'baja'], true),
                fn ($q) => $q->where('correcciones.severidad', $request->severidad));
            $query->when($request->filled('seccion') && in_array($request->seccion, ['identidad', 'ficha', 'custodia', 'catalogos'], true),
                fn ($q) => $q->where('correcciones.seccion', $request->seccion));
            $query->when($request->filled('campo'), fn ($q) => $q->where('correcciones.campo', $request->campo));
            $query->when($request->filled('equipo_id'), fn ($q) => $q->where('correcciones.equipo_id', (int) $request->equipo_id));

            if ($request->filled('q')) {
                $q = mb_strtolower(trim((string) $request->q));
                $query->where(function ($sub) use ($q) {
                    $sub->whereRaw('LOWER(correcciones.campo) LIKE ?', ["%{$q}%"])
                        ->orWhereRaw('LOWER(correcciones.etiqueta) LIKE ?', ["%{$q}%"])
                        ->orWhereRaw("LOWER(COALESCE(correcciones.valor_actual, '')) LIKE ?", ["%{$q}%"])
                        ->orWhereHas('equipo', function ($e) use ($q) {
                            $e->whereRaw("LOWER(COALESCE(nombre_equipo, '')) LIKE ?", ["%{$q}%"])
                              ->orWhereRaw("LOWER(COALESCE(serial, '')) LIKE ?", ["%{$q}%"])
                              ->orWhereRaw("LOWER(COALESCE(placa, '')) LIKE ?", ["%{$q}%"]);
                        });
                });
            }

            $ordenSeveridad = "CASE correcciones.severidad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END";
            $datos['correcciones'] = $query->orderByRaw($ordenSeveridad)
                ->orderBy('correcciones.created_at')
                ->orderBy('correcciones.id')
                ->paginate(25)
                ->withQueryString();
        }

        return view('correcciones.index', $datos);
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

    /**
     * Lote B5 — acción MASIVA sobre todos los pendientes de un campo:
     * corregir con el mismo valor o justificar con la misma causa.
     */
    public function masivo(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'campo'        => ['required', 'string', 'max:60'],
            'modo'         => ['required', 'in:resolver,ignorar'],
            'valor'        => ['nullable', 'string', 'max:500'],
            'justificacion'=> ['nullable', 'string', 'max:500'],
        ]);

        $campo = (string) $datos['campo'];
        $modo = (string) $datos['modo'];

        // La custodia es por persona: no se corrige en lote con un mismo valor.
        if ($modo === 'resolver' && str_starts_with($campo, 'custodia.')) {
            return back()->withErrors(['valor' => 'Los datos del custodio son por persona: corrija la custodia activo por activo (vista «Por activo»).']);
        }

        if ($modo === 'ignorar' && mb_strlen(trim((string) ($datos['justificacion'] ?? ''))) < 5) {
            return back()->withErrors(['justificacion' => 'La justificación del lote debe tener al menos 5 caracteres.']);
        }

        $resultado = $this->correcciones->aplicarMasivo(
            $campo,
            $modo,
            $modo === 'resolver' ? (string) $datos['valor'] : null,
            $modo === 'ignorar' ? (string) $datos['justificacion'] : null,
            null,
            $request->user()
        );

        $mensaje = $modo === 'resolver'
            ? "Lote aplicado: {$resultado['aplicadas']} de {$resultado['total']} pendientes de «{$campo}» corregidos."
            : "Lote justificado: {$resultado['aplicadas']} de {$resultado['total']} pendientes de «{$campo}» archivados.";

        if ($resultado['errores'] !== []) {
            $mensaje .= ' ' . count($resultado['errores']) . ' fila(s) con error (revisa la lista).';
        }

        return back()->with('success', $mensaje);
    }
}
