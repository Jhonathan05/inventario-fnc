<?php

namespace App\Http\Controllers;

use App\Models\AutorizacionActivo;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Services\Importadores\CMDBMapperService;

class FuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Funcionario::withCount([
            'equiposAsignados',
            'autorizacionesActivos as autorizaciones_disponibles_count' => fn ($q) => $q->disponibles(),
            'autorizacionesActivos as autorizaciones_total_count',
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('area')) {
            $query->where('area', 'like', '%' . $request->area . '%');
        }

        if ($request->filled('tipo_vinculacion')) {
            $query->where('tipo_vinculacion', $request->tipo_vinculacion);
        }

        // Obtener valores distintos para los selects de filtro
        $areasDisponibles = Funcionario::select('area')->whereNotNull('area')->distinct()->orderBy('area')->pluck('area');
        $tiposVinculacion = Funcionario::select('tipo_vinculacion')->whereNotNull('tipo_vinculacion')->distinct()->orderBy('tipo_vinculacion')->pluck('tipo_vinculacion');

        // Obtenemos todos los resultados que cumplen los filtros base
        $todos = $query->get();

        if ($request->filled('buscar')) {
            $palabras = array_filter(explode(' ', strtolower(trim($request->buscar))), fn($p) => strlen(trim($p)) > 0);
            
            $todos = $todos->filter(function ($f) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $coincidePalabra = str_contains(strtolower($f->nombres ?? ''), $palabra) ||
                                       str_contains(strtolower($f->apellidos ?? ''), $palabra) ||
                                       str_contains(strtolower($f->identificacion ?? ''), $palabra) ||
                                       str_contains(strtolower($f->cargo ?? ''), $palabra) ||
                                       str_contains(strtolower($f->area ?? ''), $palabra) ||
                                       str_contains(strtolower($f->seccional ?? ''), $palabra) ||
                                       str_contains(strtolower($f->distrito ?? ''), $palabra);
                    
                    if (!$coincidePalabra) {
                        return false;
                    }
                }
                return true;
            });
        }

        $page = request()->get('page', 1);
        $perPage = 15;
        $funcionarios = new \Illuminate\Pagination\LengthAwarePaginator(
            $todos->sortBy('nombres')->slice(($page - 1) * $perPage, $perPage)->values(),
            $todos->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('funcionarios.index', compact('funcionarios', 'areasDisponibles', 'tiposVinculacion'));
    }

    public function storeAutorizacion(Request $request, Funcionario $funcionario)
    {
        $request->validate([
            'archivo_autorizacion' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $archivo = $request->file('archivo_autorizacion');
        $ruta = $archivo->store('autorizaciones_activos', 'local');

        AutorizacionActivo::create([
            'funcionario_id' => $funcionario->id,
            'cedula' => $funcionario->identificacion,
            'nombre_funcionario' => $funcionario->nombre_completo,
            'archivo' => $ruta,
            'mime_type' => $archivo->getMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'estado' => AutorizacionActivo::ESTADO_CARGADA,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('funcionarios.index')
            ->with('success', 'Autorización cargada correctamente para ' . $funcionario->nombre_completo . '.');
    }

    public function anularAutorizacion(Request $request, Funcionario $funcionario, AutorizacionActivo $autorizacion)
    {
        if ((int) $autorizacion->funcionario_id !== (int) $funcionario->id) {
            throw ValidationException::withMessages([
                'autorizacion' => 'La autorización no corresponde al funcionario seleccionado.',
            ]);
        }

        if ($autorizacion->estado !== AutorizacionActivo::ESTADO_CARGADA) {
            throw ValidationException::withMessages([
                'autorizacion' => 'Solo se pueden anular autorizaciones en estado cargada.',
            ]);
        }

        $validated = $request->validate([
            'motivo_anulacion' => ['nullable', 'string', 'max:500'],
        ]);

        $autorizacion->update([
            'estado' => AutorizacionActivo::ESTADO_ANULADA,
            'anulada_en' => now(),
            'anulada_por_user_id' => Auth::id(),
            'motivo_anulacion' => trim((string) ($validated['motivo_anulacion'] ?? '')) ?: 'Anulación manual',
        ]);

        return redirect()
            ->route('funcionarios.show', $funcionario)
            ->with('success', 'Autorización anulada correctamente.');
    }

    public function descargarAutorizacion(Funcionario $funcionario, AutorizacionActivo $autorizacion)
    {
        if ((int) $autorizacion->funcionario_id !== (int) $funcionario->id) {
            abort(403, 'La autorización no corresponde al funcionario seleccionado.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($autorizacion->archivo)) {
            abort(404, 'El archivo no se encuentra disponible.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->response($autorizacion->archivo);
    }

    public function create()
    {
        return view('funcionarios.create');
    }

    public function show(Funcionario $funcionario)
    {
        $funcionario->load([
            'equiposAsignados' => function ($q) {
                $q->with('equipo:id,nombre_equipo,serial,estado_operativo');
            },
            'autorizacionesActivos' => function ($q) {
                $q->latest();
            },
        ])->loadCount(['equiposAsignados', 'autorizacionesActivos']);

        return view('funcionarios.show', compact('funcionario'));
    }

    public function edit(Funcionario $funcionario)
    {
        return view('funcionarios.edit', compact('funcionario'));
    }

    public function store(Request $request)
    {
        $validated = $this->validarFuncionario($request);

        Funcionario::create($validated);

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario registrado exitosamente.');
    }

    public function update(Request $request, Funcionario $funcionario)
    {
        $validated = $this->validarFuncionario($request, $funcionario->id);
        $funcionario->update($validated);

        return redirect()
            ->route('funcionarios.show', $funcionario)
            ->with('success', 'Funcionario actualizado exitosamente.');
    }

    private function validarFuncionario(Request $request, ?int $funcionarioId = null): array
    {
        $request->merge([
            'identificacion' => trim($request->identificacion),
            'nombres' => trim($request->nombres),
        ]);

        $identificacionNormalizada = CMDBMapperService::normalizeIdentifier($request->identificacion, true) ?? '';
        $hash = hash_hmac('sha256', $identificacionNormalizada, config('app.key'));

        $uniqueRule = Rule::unique('funcionarios', 'identificacion_hash');
        if ($funcionarioId) {
            $uniqueRule->ignore($funcionarioId);
        }

        // Custom validation message for the hash unique rule
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['identificacion_hash' => $hash] + $request->all(),
            [
                'identificacion_hash' => [$uniqueRule],
                'identificacion' => 'required|string',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'seccional' => 'nullable|string|max:100',
            'distrito' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
            'empresa_funcionario' => 'nullable|string|max:150',
            'tipo_vinculacion' => 'nullable|string|max:100',
            'estado' => 'required|in:Activo,Inactivo',
            ],
            [
                'identificacion_hash.unique' => 'La identificación ingresada ya se encuentra registrada.',
            ]
        );

        return $validator->validate();
    }
}
