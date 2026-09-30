<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\HistorialAdministrativo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistorialAdministrativoController extends Controller
{
    /**
     * Historial administrativo de un equipo (solo lectura).
     */
    public function porEquipo(Equipo $equipo): View
    {
        $registros = $equipo->historialAdministrativo()
            ->with('realizadoPor')
            ->paginate(20);

        $tiposCambio = HistorialAdministrativo::TIPOS_CAMBIO;

        return view('historial_administrativo.por_equipo', compact('equipo', 'registros', 'tiposCambio'));
    }

    /**
     * Listado global con filtros.
     */
    public function index(Request $request): View
    {
        $query = HistorialAdministrativo::with(['equipo', 'realizadoPor'])
            ->when($request->filled('buscar'), fn($q) =>
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->where('descripcion', 'like', "%{$request->buscar}%")
                        ->orWhereHas('equipo', fn($e) =>
                            $e->where('nombre_equipo', 'like', "%{$request->buscar}%")
                        );
                })
            )
            ->when($request->filled('tipo_cambio'), fn($q) =>
                $q->where('tipo_cambio', $request->tipo_cambio)
            )
            ->orderByDesc('created_at');

        $registros   = $query->paginate(20)->withQueryString();
        $tiposCambio = HistorialAdministrativo::TIPOS_CAMBIO;

        return view('historial_administrativo.index', compact('registros', 'tiposCambio'));
    }
}
