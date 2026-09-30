<?php

namespace App\Http\Controllers;

use App\Models\Suscripcion;
use App\Models\SuscripcionHistorial;
use Illuminate\Http\Request;
use App\Http\Requests\SuscripcionRequest;

class SuscripcionController extends Controller
{
    public function index(Request $request)
    {
        $query = Suscripcion::withCount([
            'asignaciones as cantidad_asignada_count' => function ($q) {
                $q->where('estado', 'Activa');
            },
        ]);
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('fabricante', 'like', '%' . $request->search . '%');
        }
        $suscripciones = $query->paginate(10);
        return view('suscripciones.index', compact('suscripciones'));
    }

    public function create()
    {
        return view('suscripciones.create');
    }

    public function store(SuscripcionRequest $request)
    {
        $suscripcion = Suscripcion::create($request->validated());

        SuscripcionHistorial::create([
            'suscripcion_id' => $suscripcion->id,
            'accion' => 'Creación',
            'detalles' => 'Suscripción registrada en el sistema',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('suscripciones.index')->with('success', 'Suscripción creada exitosamente.');
    }

    public function show(Suscripcion $suscripcione)
    {
        return view('suscripciones.show', compact('suscripcione'));
    }

    public function edit(Suscripcion $suscripcione)
    {
        return view('suscripciones.edit', compact('suscripcione'));
    }

    public function update(SuscripcionRequest $request, Suscripcion $suscripcione)
    {
        $suscripcione->update($request->validated());

        SuscripcionHistorial::create([
            'suscripcion_id' => $suscripcione->id,
            'accion' => 'Modificación',
            'detalles' => 'Datos de suscripción actualizados',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('suscripciones.index')->with('success', 'Suscripción actualizada.');
    }

    public function destroy(Suscripcion $suscripcione)
    {
        $suscripcione->delete();

        SuscripcionHistorial::create([
            'suscripcion_id' => $suscripcione->id,
            'accion' => 'Eliminación',
            'detalles' => 'Suscripción eliminada (soft delete)',
            'usuario_sistema_id' => auth()->id()
        ]);

        return redirect()->route('suscripciones.index')->with('success', 'Suscripción eliminada.');
    }
}
