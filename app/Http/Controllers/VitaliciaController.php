<?php

namespace App\Http\Controllers;

use App\Models\Vitalicia;
use App\Models\VitaliciaHistorial;
use Illuminate\Http\Request;
use App\Http\Requests\VitaliciaRequest;

class VitaliciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Vitalicia::withCount([
            'asignaciones as cantidad_asignada_count',
        ]);
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('fabricante', 'like', '%' . $request->search . '%');
        }
        $vitalicias = $query->paginate(10);
        return view('vitalicias.index', compact('vitalicias'));
    }

    public function create()
    {
        return view('vitalicias.create');
    }

    public function store(VitaliciaRequest $request)
    {
        $vitalicia = Vitalicia::create($request->validated());

        VitaliciaHistorial::create([
            'vitalicia_id' => $vitalicia->id,
            'accion' => 'Creación',
            'detalles' => 'Licencia vitalicia registrada',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('vitalicias.index')->with('success', 'Licencia Vitalicia creada exitosamente.');
    }

    public function show(Vitalicia $vitalicia)
    {
        return view('vitalicias.show', compact('vitalicia'));
    }

    public function edit(Vitalicia $vitalicia)
    {
        return view('vitalicias.edit', compact('vitalicia'));
    }

    public function update(VitaliciaRequest $request, Vitalicia $vitalicia)
    {
        $vitalicia->update($request->validated());

        VitaliciaHistorial::create([
            'vitalicia_id' => $vitalicia->id,
            'accion' => 'Modificación',
            'detalles' => 'Datos de licencia actualizados',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('vitalicias.index')->with('success', 'Licencia Vitalicia actualizada.');
    }

    public function destroy(Vitalicia $vitalicia)
    {
        $vitalicia->delete();

        VitaliciaHistorial::create([
            'vitalicia_id' => $vitalicia->id,
            'accion' => 'Eliminación',
            'detalles' => 'Licencia eliminada (soft delete)',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('vitalicias.index')->with('success', 'Licencia Vitalicia eliminada.');
    }
}
