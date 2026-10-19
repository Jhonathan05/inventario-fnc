<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tareas del panel de control. Sólo gestiona las tareas creadas manualmente:
 * las derivadas de datos incompletos de la CMDB se calculan al vuelo y se
 * resuelven editando el activo o funcionario de origen.
 */
class TareaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:4', 'max:160'],
            'prioridad' => ['nullable', 'in:' . implode(',', Tarea::PRIORIDADES)],
            'modulo' => ['nullable', 'in:' . implode(',', Tarea::MODULOS)],
        ], [], [
            'titulo' => 'descripción de la tarea',
        ]);

        Tarea::create([
            'titulo' => $datos['titulo'],
            'prioridad' => $datos['prioridad'] ?? 'Media',
            'modulo' => $datos['modulo'] ?? 'general',
            'estado' => Tarea::ESTADO_PENDIENTE,
            'created_by' => Auth::id(),
        ]);

        return back()->with('ok', 'Tarea creada.');
    }

    public function toggle(Tarea $tarea): RedirectResponse
    {
        if ($tarea->estado === Tarea::ESTADO_PENDIENTE) {
            $tarea->update([
                'estado' => Tarea::ESTADO_HECHA,
                'hecha_por' => Auth::id(),
                'hecha_en' => now(),
            ]);

            return back()->with('ok', 'Tarea marcada como hecha.');
        }

        $tarea->update([
            'estado' => Tarea::ESTADO_PENDIENTE,
            'hecha_por' => null,
            'hecha_en' => null,
        ]);

        return back()->with('ok', 'Tarea reabierta.');
    }

    public function destroy(Tarea $tarea): RedirectResponse
    {
        $tarea->delete();

        return back()->with('ok', 'Tarea eliminada.');
    }
}
