<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoRecursoRequest;
use App\Models\TipoRecurso;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoRecursoController extends Controller
{
    public function index(): View
    {
        $tipoRecursos = TipoRecurso::withCount('equipos')->with('complementosDefinidos')->orderBy('id', 'asc')->paginate(20);
        $catalogoComplementos = \App\Models\CatalogoComplemento::orderBy('nombre')->get();
        return view('tipo_recursos.index', compact('tipoRecursos', 'catalogoComplementos'));
    }

    public function create(): View
    {
        $catalogoComplementos = \App\Models\CatalogoComplemento::orderBy('nombre')->get();
        return view('tipo_recursos.create', compact('catalogoComplementos'));
    }

    public function store(TipoRecursoRequest $request): RedirectResponse
    {
        $tipo = TipoRecurso::create($request->validated());

        if ($request->has('complementos_ids')) {
            // Asignar orden automáticamente
            $syncData = [];
            foreach ($request->complementos_ids as $index => $id) {
                $syncData[$id] = ['orden' => $index + 1];
            }
            $tipo->complementosDefinidos()->sync($syncData);
        }

        return redirect()->route('tipo-recursos.index')
            ->with('success', 'Tipo de recurso creado correctamente.');
    }

    public function edit(TipoRecurso $tipoRecurso): View
    {
        $tipoRecurso->load('complementosDefinidos');
        $catalogoComplementos = \App\Models\CatalogoComplemento::orderBy('nombre')->get();
        return view('tipo_recursos.edit', compact('tipoRecurso', 'catalogoComplementos'));
    }

    public function update(TipoRecursoRequest $request, TipoRecurso $tipoRecurso): RedirectResponse
    {
        $tipoRecurso->update($request->validated());

        if ($request->has('complementos_ids')) {
            $syncData = [];
            foreach ($request->complementos_ids as $index => $id) {
                $syncData[$id] = ['orden' => $index + 1];
            }
            $tipoRecurso->complementosDefinidos()->sync($syncData);
        } else {
            $tipoRecurso->complementosDefinidos()->sync([]);
        }

        return redirect()->route('tipo-recursos.index')
            ->with('success', 'Tipo de recurso actualizado correctamente.');
    }

    public function destroy(TipoRecurso $tipoRecurso): RedirectResponse
    {
        // Evitar eliminar si tiene equipos asociados
        if ($tipoRecurso->equipos()->count() > 0) {
            return redirect()->route('tipo-recursos.index')
                ->with('error', 'No se puede eliminar: tiene equipos asociados.');
        }

        $tipoRecurso->delete();

        return redirect()->route('tipo-recursos.index')
            ->with('success', 'Tipo de recurso eliminado correctamente.');
    }

    // ── Catálogo de Complementos ──────────────────────────────────────────────

    public function storeCatalogoComplemento(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'nombre' => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('catalogo_complementos')->whereNull('deleted_at')
            ],
            'requiere_serial' => 'nullable|boolean',
            'usa_estado' => 'nullable|boolean',
            'tipo_recursos_ids' => 'required|array|min:1',
            'tipo_recursos_ids.*' => 'exists:tipo_recursos,id',
        ]);

        $catalogo = \App\Models\CatalogoComplemento::create([
            'nombre' => $request->nombre,
            'requiere_serial' => $request->boolean('requiere_serial'),
            'usa_estado' => $request->boolean('usa_estado', true),
        ]);

        // Sync relationships with order
        $syncData = [];
        foreach ($request->tipo_recursos_ids as $index => $trId) {
            $syncData[$trId] = ['orden' => $index + 1];
        }
        $catalogo->tipoRecursos()->sync($syncData);

        return back()->with('success', 'Complemento agregado al catálogo maestro con sus compatibilidades.');
    }

    public function updateCatalogoComplemento(\Illuminate\Http\Request $request, \App\Models\CatalogoComplemento $catalogoComplemento)
    {
        $request->validate([
            'nombre' => [
                'required', 
                'string', 
                'max:100', 
                \Illuminate\Validation\Rule::unique('catalogo_complementos')->ignore($catalogoComplemento->id)->whereNull('deleted_at')
            ],
            'requiere_serial' => 'nullable|boolean',
            'usa_estado' => 'nullable|boolean',
            'tipo_recursos_ids' => 'required|array|min:1',
            'tipo_recursos_ids.*' => 'exists:tipo_recursos,id',
        ]);

        $catalogoComplemento->update([
            'nombre' => $request->nombre,
            'requiere_serial' => $request->boolean('requiere_serial'),
            'usa_estado' => $request->boolean('usa_estado', true),
        ]);

        $syncData = [];
        foreach ($request->tipo_recursos_ids as $index => $trId) {
            $syncData[$trId] = ['orden' => $index + 1];
        }
        $catalogoComplemento->tipoRecursos()->sync($syncData);

        return back()->with('success', 'Complemento actualizado correctamente.');
    }
}
