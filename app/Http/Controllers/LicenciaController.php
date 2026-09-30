<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\LicenciaHistorial;
use App\Http\Requests\StoreLicenciaRequest;
use App\Http\Requests\UpdateLicenciaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Licencia::withCount([
            'asignaciones as cupos_asignados_count' => function ($q) {
                $q->where('estado', 'Activa');
            },
        ]);

        if ($request->has('buscar') && $request->buscar != '') {
            $buscar = $request->buscar;
            $query->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('tipo_licencia', 'like', "%{$buscar}%")
                  ->orWhere('estado', 'like', "%{$buscar}%");
        }

        if ($request->has('estado') && $request->estado != '') {
            $query->where('estado', $request->estado);
        }

        $licencias = $query->paginate(10)->withQueryString();

        // Alertas
        $hoy = now();
        $alertasRojas = Licencia::where('estado', 'Vencida')
            ->orWhere('fecha_vencimiento', '<', $hoy->toDateString())
            ->count();
            
        $alertasAmarillas = Licencia::where('fecha_vencimiento', '>=', $hoy->toDateString())
            ->where('fecha_vencimiento', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where('estado', 'Activa')
            ->count();

        return view('licencias.index', compact('licencias', 'alertasRojas', 'alertasAmarillas'));
    }

    public function create()
    {
        return view('licencias.create');
    }

    public function store(StoreLicenciaRequest $request)
    {
        $licencia = Licencia::create($request->validated() + ['created_by' => Auth::id()]);

        // Registrar en historial
        LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => Auth::id(),
            'accion' => 'Creación de licencia',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => 'Licencia creada con éxito',
        ]);

        return redirect()->route('licencias.index')->with('success', 'Licencia registrada exitosamente.');
    }

    public function show(Licencia $licencia)
    {
        $licencia->loadCount([
            'asignaciones as cupos_asignados_count' => function ($q) {
                $q->where('estado', 'Activa');
            },
        ]);
        $licencia->load(['asignaciones.funcionario', 'asignaciones.equipo', 'asignaciones.serial', 'seriales']);
        $historial = LicenciaHistorial::where('licencia_nombre', $licencia->nombre)->orderBy('fecha', 'desc')->get();

        return view('licencias.show', compact('licencia', 'historial'));
    }

    public function edit(Licencia $licencia)
    {
        return view('licencias.edit', compact('licencia'));
    }

    public function update(UpdateLicenciaRequest $request, Licencia $licencia)
    {
        $licencia->update($request->validated() + ['updated_by' => Auth::id()]);

        // Registrar en historial
        LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => Auth::id(),
            'accion' => 'Actualización de licencia',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => 'Información de la licencia actualizada',
        ]);

        return redirect()->route('licencias.index')->with('success', 'Licencia actualizada exitosamente.');
    }

    public function destroy(Licencia $licencia)
    {
        // Registrar en historial
        LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => Auth::id(),
            'accion' => 'Eliminación lógica',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => 'La licencia fue enviada a la papelera',
        ]);

        $licencia->delete();

        return redirect()->route('licencias.index')->with('success', 'Licencia eliminada exitosamente.');
    }

    public function reportes()
    {
        // Esto podría cargar una vista específica de reportes para licencias
        return view('licencias.reportes');
    }

    public function exportar(Request $request)
    {
        $filtros = $request->only(['buscar', 'estado']);
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LicenciasExport($filtros), 'reporte_licencias_' . date('Ymd_His') . '.xlsx');
    }
}
