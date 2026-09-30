<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignacionRequest;
use App\Models\Asignacion;
use App\Models\AutorizacionActivo;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Services\AsignacionService;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    public function __construct(
        private readonly AsignacionService $asignacionService,
        private readonly PdfService        $pdfService
    ) {}

    /**
     * Listado global de asignaciones con filtros.
     */
    public function index(Request $request): View
    {
        $query = Asignacion::with(['equipo', 'registradoPor'])
            ->when($request->filled('equipo'), fn($q) =>
                $q->where('equipo_id', $request->equipo)
            )
            ->when($request->filled('tipo_accion'), fn($q) =>
                $q->where('tipo_accion', $request->tipo_accion)
            )
            ->when($request->filled('buscar'), fn($q) =>
                $q->where(function($sub) use ($request) {
                    $sub->where('usuario_nombre', 'like', "%{$request->buscar}%")
                        ->orWhere('usuario_cedula', 'like', "%{$request->buscar}%")
                        ->orWhereHas('equipo', fn($e) =>
                            $e->where('nombre_equipo', 'like', "%{$request->buscar}%")
                              ->orWhere('serial', 'like', "%{$request->buscar}%")
                        );
                })
            )
            ->orderByDesc('fecha_accion');

        $asignaciones = $query->paginate(15)->withQueryString();
        $tiposAccion  = Asignacion::TIPOS_ACCION;

        return view('asignaciones.index', compact('asignaciones', 'tiposAccion'));
    }

    /**
     * Historial de asignaciones de un equipo específico.
     */
    public function porEquipo(Equipo $equipo): View
    {
        $asignaciones = $equipo->asignaciones()
            ->with('registradoPor')
            ->paginate(20);

        return view('asignaciones.por_equipo', compact('equipo', 'asignaciones'));
    }

    /**
     * Lista funcionarios elegibles para asignación rápida.
     * Elegible = sin activos o con al menos una autorización disponible para activo adicional.
     */
    public function funcionariosElegibles(Request $request): JsonResponse
    {
        $termino = trim((string) $request->query('q', ''));
        $equipoId = $request->query('equipo_id');
        $contexto = $request->query('contexto', 'asignacion');

        $esEquipoTecnologico = false;
        if ($equipoId) {
            $equipo = Equipo::with('tipoRecurso')->find($equipoId);
            if ($equipo && $equipo->tipoRecurso) {
                $tipoNombre = mb_strtolower(trim($equipo->tipoRecurso->nombre));
                $tiposTecnologicos = ['equipo escritorio', 'equipo portatil', 'equipo todo en uno', 'equipo micro'];
                if (in_array($tipoNombre, $tiposTecnologicos)) {
                    $esEquipoTecnologico = true;
                }
            }
        }

        $query = Funcionario::query()
            ->where('estado', 'Activo');

        if ($termino !== '') {
            $query->where('area', 'like', "%{$termino}%"); // Solo filtramos por area en DB inicialmente (opcional, pero mejor cargar todo Activo y filtrar para evitar omitir por OR)
            // Para asegurar que el OR funciona correctamente, quitaremos el where de area y lo haremos todo en memoria
            $query = Funcionario::query()->where('estado', 'Activo');
        }

        $funcionarios = $query->withCount([
                'equiposAsignados as activos_count' => function ($q) {
                    $q->whereHas('equipo.tipoRecurso', function ($sub) {
                        $sub->whereRaw('LOWER(nombre) IN (?, ?, ?, ?)', ['equipo escritorio', 'equipo portatil', 'equipo todo en uno', 'equipo micro']);
                    });
                },
                'autorizacionesActivos as autorizaciones_disponibles_count' => fn ($q) =>
                    $q->where('estado', AutorizacionActivo::ESTADO_CARGADA),
            ])
            ->get();

        if ($termino !== '') {
            $terminoLower = strtolower($termino);
            $funcionarios = $funcionarios->filter(function ($f) use ($terminoLower) {
                return str_contains(strtolower($f->nombres ?? ''), $terminoLower) ||
                       str_contains(strtolower($f->apellidos ?? ''), $terminoLower) ||
                       str_contains(strtolower($f->identificacion ?? ''), $terminoLower) ||
                       str_contains(strtolower($f->cargo ?? ''), $terminoLower) ||
                       str_contains(strtolower($f->area ?? ''), $terminoLower);
            });
        }

        $funcionarios = $funcionarios->sortBy('nombres')->take(200);

        $enriquecidos = $funcionarios->map(function ($f) use ($esEquipoTecnologico, $contexto) {
            $activos = (int) $f->activos_count;
            $autorizacionesDisponibles = (int) $f->autorizaciones_disponibles_count;
            
            if ($contexto === 'responsabilidad') {
                $esElegible = $autorizacionesDisponibles >= 1;
                $autorizacionesFaltantes = $esElegible ? 0 : 1;
            } else {
                if ($esEquipoTecnologico) {
                    $esElegible = $activos === 0 || $autorizacionesDisponibles >= 1;
                    $autorizacionesFaltantes = $activos > 0 && $autorizacionesDisponibles < 1 ? 1 : 0;
                } else {
                    $esElegible = true;
                    $autorizacionesFaltantes = 0;
                }
            }

            return [
                'id' => $f->id,
                'identificacion' => $f->identificacion,
                'nombre' => trim("{$f->nombres} {$f->apellidos}"),
                'cargo' => $f->cargo,
                'area' => $f->area,
                'departamento' => $f->departamento,
                'ciudad' => $f->ciudad,
                'empresa_funcionario' => $f->empresa_funcionario,
                'tipo_vinculacion' => $f->tipo_vinculacion,
                'activos_count' => $activos,
                'autorizaciones_count' => $autorizacionesDisponibles,
                'es_elegible' => $esElegible,
                'autorizaciones_faltantes' => $autorizacionesFaltantes,
            ];
        });

        $elegibles = $enriquecidos
            ->filter(fn ($f) => $f['es_elegible'])
            ->values();

        if ($contexto === 'responsabilidad') {
            // "Los demás NO aparecerán"
            $bloqueadosCoincidentes = collect([]);
        } else {
            $bloqueadosCoincidentes = $enriquecidos
                ->filter(fn ($f) => !$f['es_elegible'])
                ->values()
                ->map(function ($f) {
                    return [
                        'id' => $f['id'],
                        'identificacion' => $f['identificacion'],
                        'nombre' => $f['nombre'],
                        'activos_count' => $f['activos_count'],
                        'autorizaciones_count' => $f['autorizaciones_count'],
                        'autorizaciones_faltantes' => $f['autorizaciones_faltantes'],
                    ];
                });
        }

        return response()->json([
            'data' => $elegibles,
            'bloqueados' => $bloqueadosCoincidentes,
        ]);
    }

    /**
     * Procesa acciones sobre un equipo (asignar, reemplazar, retirar, baja, mantenimiento).
     */
    public function store(AsignacionRequest $request): RedirectResponse
    {
        $equipo  = Equipo::findOrFail($request->equipo_id);
        $usuario = Auth::user();
        $accion  = $request->tipo_accion;
        $datos   = $request->validated();

        $asignacion = match ($accion) {
            'asignacion'    => $this->asignacionService->asignar(
                $equipo, $datos, $usuario
            ),
            'reemplazo'     => $this->asignacionService->reemplazar(
                $equipo, $datos, $usuario
            ),
            'retiro'        => $this->asignacionService->retirar(
                $equipo, $request->motivo, $usuario, $request->observaciones
            ),
            'mantenimiento' => $this->asignacionService->pasarAMantenimiento(
                $equipo, $request->motivo, $usuario, $request->observaciones
            ),
            'baja'          => $this->asignacionService->darDeBaja(
                $equipo, $request->motivo, $usuario, $request->observaciones
            ),
            'restauracion'  => $this->asignacionService->restaurar(
                $equipo, $request->motivo, $usuario, $request->observaciones
            ),
            default         => abort(422, 'Acción no válida'),
        };

        $returnTo = (string) $request->input('return_to', '');

        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/equipos') || str_contains($path, '/historial-tecnico')) {
                    return redirect($returnTo)->with('success', 'Acción registrada correctamente.');
                }
            }
        }

        return redirect()
            ->route('equipos.show', $equipo)
            ->with('success', 'Acción registrada correctamente.');
    }

    /**
     * Detalle de una asignación.
     */
    public function show(Asignacion $asignacion): View
    {
        $asignacion->load(['equipo.tipoRecurso', 'registradoPor']);
        return view('asignaciones.show', compact('asignacion'));
    }

    /**
     * Genera y descarga el PDF del acta de entrega.
     */
    public function generarPdf(Asignacion $asignacion)
    {
        return $this->pdfService->generarActaEntrega($asignacion);
    }
}
