<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Equipo;
use App\Services\ActaService;
use App\Services\HistorialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActaController extends Controller
{
    public function __construct(
        private ActaService $actaService,
    ) {}

    /**
     * Formulario de nueva acta de novedad de activos.
     */
    public function create(Request $request): View
    {
        $tipos = Acta::TIPOS_ACCION;
        $reglas = Acta::REGLAS_MOVIMIENTO;
        $returnTo = $request->input('return_to', route('inicio'));

        return view('actas.create', compact('tipos', 'reglas', 'returnTo'));
    }

    /**
     * Registra el acta con sus ítems en transacción.
     */
    public function store(Request $request): RedirectResponse
    {
        // Normalizar el tipo a minúsculas
        if ($request->has('tipo_accion')) {
            $request->merge(['tipo_accion' => strtolower(trim($request->input('tipo_accion')))]);
        }

        $datos = $request->validate([
            'tipo_accion' => ['required', 'string', 'in:' . implode(',', array_keys(Acta::TIPOS_ACCION))],
            'ubicacion' => ['nullable', 'string', 'max:100'],
            'fecha_accion' => ['nullable', 'date'],
            'destino' => ['nullable', 'string', 'in:disponible,baja'],
            'fecha_inicio' => ['nullable', 'date', 'required_if:tipo_accion,prestamo'],
            'fecha_fin_probable' => ['nullable', 'date', 'required_if:tipo_accion,prestamo'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'entregador_dependencia' => ['nullable', 'string', 'max:150'],
            'entregador_nombre' => ['nullable', 'string', 'max:150'],
            'entregador_codigo' => ['nullable', 'string', 'max:30'],
            'entregador_cargo' => ['nullable', 'string', 'max:150'],
            'recibe_dependencia' => ['nullable', 'string', 'max:150'],
            'recibe_nombre' => ['nullable', 'string', 'max:150'],
            'recibe_codigo' => ['nullable', 'string', 'max:30'],
            'recibe_cargo' => ['nullable', 'string', 'max:150'],
            'usuario_empresa_propietaria' => ['nullable', 'string', 'max:100'],
            'usuario_dependencia' => ['nullable', 'string', 'max:150'],
            'usuario_fuente_recurso' => ['nullable', 'string', 'max:50'],
            'usuario_empresa_funcionario' => ['nullable', 'string', 'max:100'],
            'usuario_tipo_vinculacion' => ['nullable', 'string', 'max:50'],
            'usuario_cedula' => ['nullable', 'string', 'max:30'],
            'usuario_shortname' => ['nullable', 'string', 'max:100'],
            'usuario_nombre' => ['nullable', 'string', 'max:150'],
            'usuario_departamento' => ['nullable', 'string', 'max:100'],
            'usuario_ciudad' => ['nullable', 'string', 'max:100'],
            'usuario_cargo' => ['nullable', 'string', 'max:150'],
            'usuario_area' => ['nullable', 'string', 'max:150'],
            'usuario_piso' => ['nullable', 'string', 'max:50'],
            'usuario_distrito' => ['nullable', 'string', 'max:150'],
            'usuario_seccional' => ['nullable', 'string', 'max:150'],
            'equipos' => ['required', 'array', 'min:1'],
            'equipos.*' => ['required', 'integer', 'exists:equipos,id'],
            'ficha' => ['nullable', 'array'],
        ]);

        $equipos = Equipo::whereIn('id', $datos['equipos'])->get();
        $fichas = $datos['ficha'] ?? [];

        try {
            $acta = $this->actaService->registrar($datos, $equipos, $fichas, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['equipos' => $e->getMessage()])->withInput();
        }

        $returnTo = $request->input('return_to', '');
        if (is_string($returnTo) && $returnTo !== '') {
            $path = parse_url($returnTo, PHP_URL_PATH);
            if (is_string($path) && (str_contains($path, '/equipos') || str_contains($path, '/inicio') || str_contains($path, '/dashboard'))) {
                return redirect($returnTo)->with('success', "Acta #{$acta->id} registrada correctamente.");
            }
        }

        return redirect()->route('actas.show', $acta)->with('success', "Acta #{$acta->id} registrada correctamente.");
    }

    /**
     * Detalle del acta con sus ítems y paquete documental.
     */
    public function show(Acta $acta): View
    {
        $acta->load(['items.equipo.tipoRecurso', 'registradoPor']);

        return view('actas.show', compact('acta'));
    }

    /**
     * AJAX: busca activos en el estado requerido por el tipo de movimiento.
     */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $tipo = strtolower(trim((string) $request->input('tipo', 'inventario_fisico')));

        $regla = Acta::REGLAS_MOVIMIENTO[$tipo] ?? null;
        $estadosValidos = $regla['requiere'] ?? ['disponible'];

        $query = Equipo::query()
            ->with('tipoRecurso:id,nombre')
            ->whereNull('deleted_at');

        if (! in_array('todos', $estadosValidos, true)) {
            $query->whereIn('estado_operativo', $estadosValidos);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre_equipo', 'like', "%{$q}%")
                    ->orWhere('serial', 'like', "%{$q}%")
                    ->orWhere('placa', 'like', "%{$q}%")
                    ->orWhere('marca', 'like', "%{$q}%")
                    ->orWhere('modelo', 'like', "%{$q}%")
                    ->orWhereHas('tipoRecurso', fn ($t) => $t->where('nombre', 'like', "%{$q}%"));
            });
        }

        $equipos = $query->orderBy('nombre_equipo')
            ->limit(15)
            ->get(['id', 'nombre_equipo', 'serial', 'placa', 'marca', 'modelo', 'estado_operativo', 'tipo_recurso_id']);

        return response()->json([
            'resultados' => $equipos->map(fn (Equipo $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre_equipo ?: $e->serial,
                'serial' => $e->serial,
                'placa' => $e->placa,
                'marca' => $e->marca,
                'modelo' => $e->modelo,
                'tipo' => $e->tipoRecurso?->nombre ?? 'N/A',
                'estado' => $e->estado_operativo,
            ]),
        ]);
    }
}
