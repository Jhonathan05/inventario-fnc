<?php

namespace App\Http\Controllers;

use App\Models\ActivoComplemento;
use App\Models\CatalogoComplemento;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplementoGlobalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // 1. Dashboard metrics
        $total = ActivoComplemento::count();
        $disponibles = ActivoComplemento::disponibles()->count();
        $asignados = ActivoComplemento::asignados()->count();
        $danados = ActivoComplemento::dañados()->count();
        $enReparacion = ActivoComplemento::enReparacion()->count();
        $extraviados = ActivoComplemento::extraviados()->count();
        $bajas = ActivoComplemento::bajas()->count();

        // 2. Query for DataTable with Eager Loading (N+1 safe)
        $query = ActivoComplemento::with(['equipo.tipoRecurso', 'equipo.usuarioAsignado', 'catalogoComplemento.tipoRecursos'])
                    ->orderBy('created_at', 'desc');

        // Advanced Filters
        if ($request->filled('estado')) {
            if ($request->estado === 'Disponible') {
                $query->disponibles();
            } else {
                $query->where('estado', $request->estado);
            }
        }
        
        if ($request->filled('catalogo_id')) {
            $query->where('catalogo_complemento_id', $request->catalogo_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('serial', 'like', "%{$buscar}%")
                  ->orWhereHas('equipo', function($qEq) use ($buscar) {
                      $qEq->where('nombre_equipo', 'like', "%{$buscar}%")
                          ->orWhere('placa', 'like', "%{$buscar}%");
                  });
            });
        }

        $complementos = $query->paginate(20)->withQueryString();
        $catalogo = CatalogoComplemento::orderBy('nombre')->get();

        return view('complementos.index', compact(
            'complementos',
            'catalogo',
            'total',
            'disponibles',
            'asignados',
            'danados',
            'enReparacion',
            'extraviados',
            'bajas'
        ));
    }
}
