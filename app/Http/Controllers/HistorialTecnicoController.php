<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistorialTecnicoRequest;
use App\Models\Equipo;
use App\Models\HistorialTecnico;
use App\Models\User;
use App\Services\HistorialTecnicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HistorialTecnicoController extends Controller
{
    public function __construct(
        private readonly HistorialTecnicoService $historialTecnicoService
    ) {}
    /**
     * Listado global con filtros y buscador.
     */
    public function index(Request $request): View
    {
        $query = HistorialTecnico::with(['equipo', 'registradoPor'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('descripcion', 'like', "%{$request->buscar}%")
                        ->orWhere('usuario_responsable', 'like', "%{$request->buscar}%")
                        ->orWhereHas('equipo', fn($e) =>
                            $e->where('nombre_equipo', 'like', "%{$request->buscar}%")
                              ->orWhere('serial', 'like', "%{$request->buscar}%")
                        );
                });
            })
            ->when($request->filled('tipo_evento'), fn($q) =>
                $q->where('tipo_evento', $request->tipo_evento)
            )
            ->when($request->filled('fecha_desde'), fn($q) =>
                $q->whereDate('fecha_evento', '>=', $request->fecha_desde)
            )
            ->when($request->filled('fecha_hasta'), fn($q) =>
                $q->whereDate('fecha_evento', '<=', $request->fecha_hasta)
            )
            ->orderByDesc('fecha_evento');

        $registros    = $query->paginate(15)->withQueryString();
        $tiposEvento  = HistorialTecnico::TIPOS_EVENTO_FORM;

        $conteosPorEstado = HistorialTecnico::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $conteoCreados = (int) ($conteosPorEstado['Creado'] ?? 0);
        $conteoProceso = (int) ($conteosPorEstado['En proceso'] ?? 0);
        $conteosSuspendidos = (int) ($conteosPorEstado['Suspendido'] ?? 0);
        $conteoFinalizados = (int) ($conteosPorEstado['Finalizado'] ?? 0);

        return view('historial_tecnico.index', compact(
            'registros', 
            'tiposEvento', 
            'conteoCreados', 
            'conteoProceso', 
            'conteosSuspendidos', 
            'conteoFinalizados'
        ));
    }

    /**
     * Timeline de historial técnico de un equipo específico.
     */
    public function porEquipo(Request $request, Equipo $equipo): View
    {
        $registros   = $equipo->historialTecnico()->with('registradoPor')->get();
        $tiposEvento = HistorialTecnico::TIPOS_EVENTO_FORM;
        $volverUrl   = route('historial-tecnico.index');

        $returnTo = $request->query('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/historial-tecnico') || str_contains($path, '/equipos')) {
                    $volverUrl = $returnTo;
                }
            }
        }

        return view('historial_tecnico.por_equipo', compact('equipo', 'registros', 'tiposEvento', 'volverUrl'));
    }

    /**
     * Formulario de creación.
     */
    public function create(Request $request): View
    {
        $tiposEvento = HistorialTecnico::TIPOS_EVENTO_FORM;
        $equipoId    = $request->equipo_id;
        $equipo      = $equipoId ? Equipo::find($equipoId) : null;
        $volverUrl   = $equipo ? route('historial-tecnico.por-equipo', $equipo) : route('historial-tecnico.index');

        $returnTo = $request->query('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/historial-tecnico') || str_contains($path, '/equipos')) {
                    $volverUrl = $returnTo;
                }
            }
        }

        $analistasSoporte = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Analista Tic'))
            ->select('name')
            ->get();
        $responsableSugerido = $analistasSoporte->count() === 1
            ? (string) $analistasSoporte->first()->name
            : 'Analista TIC';
        $equipos     = Equipo::select('id', 'nombre_equipo', 'serial')
            ->orderBy('nombre_equipo')
            ->limit(500)
            ->get();

        return view('historial_tecnico.create', compact('tiposEvento', 'equipo', 'equipos', 'responsableSugerido', 'volverUrl'));
    }

    /**
     * Guarda nuevo evento técnico con snapshot del usuario actual.
     */
    public function store(HistorialTecnicoRequest $request): RedirectResponse
    {
        $equipo = Equipo::findOrFail($request->equipo_id);

        $this->historialTecnicoService->registrarEvento(
            $equipo,
            $request->validated(),
            $request->file('archivos'),
            auth()->id()
        );

        return redirect()
            ->route('historial-tecnico.por-equipo', $equipo)
            ->with('success', 'Evento técnico registrado correctamente.');
    }

    /**
     * Detalle de un evento técnico.
     */
    public function show(HistorialTecnico $historialTecnico): View
    {
        $historialTecnico->load(['equipo', 'registradoPor']);
        $puedeModificarBitacora = $this->puedeModificarBitacora($historialTecnico);

        return view('historial_tecnico.show', compact('historialTecnico', 'puedeModificarBitacora'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Request $request, HistorialTecnico $historialTecnico): View|RedirectResponse
    {
        if (!$this->puedeModificarBitacora($historialTecnico)) {
            return redirect()
                ->route('historial-tecnico.show', $historialTecnico)
                ->with('warning', 'La bitácora del activo restaurado es de solo lectura y no se puede editar.');
        }

        $volverUrl = route('historial-tecnico.show', $historialTecnico);
        $returnTo = $request->query('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/historial-tecnico') || str_contains($path, '/equipos')) {
                    $volverUrl = $returnTo;
                }
            }
        }

        $tiposEvento = HistorialTecnico::TIPOS_EVENTO_FORM;
        $historialTecnico->load('equipo');
        return view('historial_tecnico.edit', compact('historialTecnico', 'tiposEvento', 'volverUrl'));
    }

    /**
     * Actualizar evento técnico.
     */
    public function update(HistorialTecnicoRequest $request, HistorialTecnico $historialTecnico): RedirectResponse
    {
        if (!$this->puedeModificarBitacora($historialTecnico)) {
            return redirect()
                ->route('historial-tecnico.show', $historialTecnico)
                ->with('warning', 'La bitácora del activo restaurado es de solo lectura y no se puede editar.');
        }

        $historialTecnico->update($request->validated());

        $returnTo = $request->input('return_to');
        $redirectUrl = route('historial-tecnico.show', $historialTecnico);
        
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path)) {
                $path = strtolower($path);
                if (str_contains($path, '/historial-tecnico') || str_contains($path, '/equipos')) {
                    $redirectUrl = $returnTo;
                }
            }
        }

        return redirect($redirectUrl)
            ->with('success', 'Evento técnico actualizado correctamente.');
    }

    /**
     * Eliminar (soft delete).
     */
    public function destroy(HistorialTecnico $historialTecnico): RedirectResponse
    {
        if (!$this->puedeModificarBitacora($historialTecnico)) {
            return redirect()
                ->route('historial-tecnico.show', $historialTecnico)
                ->with('warning', 'La bitácora del activo restaurado es de solo lectura y no se puede eliminar.');
        }

        $equipoId = $historialTecnico->equipo_id;
        $historialTecnico->delete();

        return redirect()
            ->route('historial-tecnico.por-equipo', $equipoId)
            ->with('success', 'Evento técnico eliminado.');
    }

    private function puedeModificarBitacora(HistorialTecnico $historialTecnico): bool
    {
        $historialTecnico->loadMissing('equipo:id,estado_operativo');

        return in_array((string) $historialTecnico->equipo?->estado_operativo, ['mantenimiento', 'baja'], true);
    }
}
