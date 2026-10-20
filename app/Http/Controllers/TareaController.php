<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Tareas del panel de control. Sólo gestiona las tareas creadas manualmente:
 * las derivadas de datos incompletos de la CMDB se calculan al vuelo y se
 * resuelven editando el activo o funcionario de origen.
 */
class TareaController extends Controller
{
    /**
     * Tablero completo de tareas (patrón kanban de fnc-ssp-platform):
     * columnas por prioridad + derivadas CMDB, filtros GET idempotentes y
     * cierre/apertura como transiciones del tablero.
     */
    public function index(Request $request, DashboardService $dashboard): View
    {
        $verEquipos = (bool) $request->user()?->can('equipos.ver');
        $tablero = $dashboard->tareasPendientes($verEquipos, null);

        // CapitalizeFirstLetter title-casea TODO el input (también el query
        // GET): se normaliza a minúsculas antes de comparar con los catálogos.
        $origen = mb_strtolower((string) $request->query('origen', 'todas'));
        $modulo = mb_strtolower((string) $request->query('modulo', 'todos'));
        $busqueda = trim((string) $request->query('q'));

        $items = collect($tablero['items']);

        if (in_array($origen, ['manuales', 'derivadas'], true)) {
            $items = $items->filter(fn (array $item): bool => $origen === 'manuales'
                ? $item['kind'] === 'manual'
                : $item['kind'] !== 'manual');
        }

        if (in_array($modulo, Tarea::MODULOS, true)) {
            $items = $items->filter(fn (array $item): bool => ($item['modulo'] ?? 'general') === $modulo);
        }

        if ($busqueda !== '') {
            $needle = mb_strtolower($busqueda);
            $items = $items->filter(fn (array $item): bool => str_contains(mb_strtolower((string) $item['titulo']), $needle));
        }

        $columnas = collect([
            ['key' => 'Alta', 'label' => 'Prioridad alta', 'tone' => 'danger', 'items' => $items->where('pill', 'Alta')->values()],
            ['key' => 'Media', 'label' => 'Prioridad media', 'tone' => 'warning', 'items' => $items->where('pill', 'Media')->values()],
            ['key' => 'Baja', 'label' => 'Prioridad baja', 'tone' => 'slate', 'items' => $items->where('pill', 'Baja')->values()],
            ['key' => 'CMDB', 'label' => 'Derivadas CMDB', 'tone' => 'info', 'items' => $items->where('pill', 'CMDB')->values()],
        ]);

        $hechas = Tarea::query()
            ->where('estado', Tarea::ESTADO_HECHA)
            ->orderByDesc('hecha_en')
            ->limit(20)
            ->get();

        return view('tareas.index', [
            'columnas' => $columnas,
            'totalPendientes' => (int) $tablero['total'],
            'totalVisibles' => $items->count(),
            'hechas' => $hechas,
            'filtros' => [
                'origen' => $origen,
                'modulo' => $modulo,
                'q' => $busqueda,
            ],
            'modulos' => Collection::make([
                ['valor' => 'todos', 'label' => 'Todos los módulos'],
                ['valor' => 'general', 'label' => 'General'],
                ['valor' => 'equipos', 'label' => 'Equipos'],
                ['valor' => 'funcionarios', 'label' => 'Funcionarios'],
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // El sanitize global title-casea el input: el módulo viaja en
        // minúsculas en los catálogos, así que se normaliza antes de validar.
        if ($request->input('modulo') !== null) {
            $request->merge(['modulo' => mb_strtolower(trim((string) $request->input('modulo')))]);
        }

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
