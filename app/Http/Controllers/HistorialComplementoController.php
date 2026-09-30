<?php

namespace App\Http\Controllers;

use App\Models\ActivoComplemento;
use App\Models\HistorialComplemento;
use Illuminate\Http\Request;

class HistorialComplementoController extends Controller
{
    public function global(Request $request)
    {
        $query = HistorialComplemento::with(['complemento', 'complemento.catalogoComplemento', 'usuario', 'activoOrigen', 'activoDestino'])
            ->orderBy('fecha', 'desc');

        if ($request->filled('evento')) {
            $query->where('evento', $request->evento);
        }

        if ($request->filled('complemento_id')) {
            $query->where('complemento_id', $request->complemento_id);
        }

        if ($request->filled('buscar')) {
            $termino = $request->buscar;
            $query->whereHas('complemento', function($q) use ($termino) {
                $q->where('serial', 'like', "%{$termino}%")
                  ->orWhere('marca', 'like', "%{$termino}%")
                  ->orWhere('modelo', 'like', "%{$termino}%");
            });
        }

        $historial = $query->paginate(20);
        $eventos = HistorialComplemento::select('evento')->distinct()->pluck('evento');

        return view('equipos.historial_complementos_global', compact('historial', 'eventos'));
    }

    public function individual($id)
    {
        $complemento = ActivoComplemento::with('catalogoComplemento', 'equipo')->findOrFail($id);
        
        $historial = HistorialComplemento::with(['usuario', 'activoOrigen', 'activoDestino'])
            ->where('complemento_id', $id)
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return view('equipos.historial_complemento_individual', compact('complemento', 'historial'));
    }
}
