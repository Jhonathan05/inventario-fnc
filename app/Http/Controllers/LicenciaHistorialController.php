<?php

namespace App\Http\Controllers;

use App\Models\LicenciaHistorial;
use Illuminate\Http\Request;

class LicenciaHistorialController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenciaHistorial::with('usuario')->orderBy('fecha', 'desc');

        if ($request->has('buscar') && $request->buscar != '') {
            $buscar = $request->buscar;
            $query->where('licencia_nombre', 'like', "%{$buscar}%")
                  ->orWhere('funcionario_nombre', 'like', "%{$buscar}%")
                  ->orWhere('equipo_placa', 'like', "%{$buscar}%")
                  ->orWhere('accion', 'like', "%{$buscar}%");
        }

        $historial = $query->paginate(20)->withQueryString();

        return view('licencias_historial.index', compact('historial'));
    }
}
