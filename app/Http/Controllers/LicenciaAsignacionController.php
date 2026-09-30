<?php

namespace App\Http\Controllers;

use App\Models\LicenciaAsignacion;
use App\Models\Licencia;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\LicenciaHistorial;
use App\Http\Requests\StoreLicenciaAsignacionRequest;
use App\Http\Requests\UpdateLicenciaAsignacionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicenciaAsignacionController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenciaAsignacion::with(['licencia', 'equipo', 'funcionario']);

        if ($request->has('estado') && $request->estado != '') {
            $query->where('estado', $request->estado);
        }

        $asignaciones = $query->paginate(10)->withQueryString();
        
        return view('licencias_asignaciones.index', compact('asignaciones'));
    }

    public function create()
    {
        $licencias = Licencia::where('estado', 'Activa')
            ->withCount([
                'asignaciones as cupos_asignados_count' => function ($q) {
                    $q->where('estado', 'Activa');
                },
            ])
            ->get();
        $equipos = Equipo::with('usuarioAsignado:id,equipo_id,cedula')
            ->select('id', 'nombre_equipo', 'activo_fijo', 'serial')
            ->where('estado_operativo', 'activo')
            ->orderBy('nombre_equipo')
            ->get();
        $funcionarios = Funcionario::select('id', 'nombres', 'apellidos', 'identificacion')
            ->where('estado', 'activo')
            ->get()
            ->sortBy('nombres');
        
        return view('licencias_asignaciones.create', compact('licencias', 'equipos', 'funcionarios'));
    }

    public function store(StoreLicenciaAsignacionRequest $request)
    {
        $licencia = Licencia::findOrFail($request->licencia_id);
        
        // Validación de fechas
        if ($request->fecha_vencimiento && $licencia->fecha_vencimiento) {
            $fechaVencimientoReq = \Carbon\Carbon::parse($request->fecha_vencimiento);
            if ($fechaVencimientoReq->gt($licencia->fecha_vencimiento)) {
                return back()->withInput()->withErrors(['fecha_vencimiento' => 'La fecha de vencimiento de la asignación no puede ser superior al vencimiento de la licencia (' . $licencia->fecha_vencimiento->format('d/m/Y') . ').']);
            }
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $licencia) {
                $serial = null;
                $totalSeriales = \App\Models\LicenciaSerial::where('licencia_id', $licencia->id)->count();
                
                if ($totalSeriales > 0) {
                    $serial = \App\Models\LicenciaSerial::where('licencia_id', $licencia->id)
                        ->where('estado', 'Disponible')
                        ->lockForUpdate()
                        ->first();
                        
                    if (!$serial) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'licencia_id' => 'No existen seriales disponibles para esta licencia.'
                        ]);
                    }
                    
                    $serial->estado = 'Reservado';
                    $serial->save();
                }

                $data = $request->validated();
                $data['created_by'] = Auth::id();
                if ($serial) {
                    $data['licencia_serial_id'] = $serial->id;
                }

                $asignacion = LicenciaAsignacion::create($data);
                $asignacion->load(['licencia', 'equipo', 'funcionario']);

                if ($serial) {
                    $serial->estado = 'Asignado';
                    $serial->save();
                }

                // Registrar historial
                $licenciaNombre = $asignacion->licencia ? $asignacion->licencia->nombre : 'Sin licencia';
                $equipoPlaca = $asignacion->equipo ? $asignacion->equipo->placa : 'N/A';
                $funcionarioNombre = $asignacion->funcionario ? $asignacion->funcionario->nombre_completo : 'N/A';
                $serialStr = $serial ? $serial->serial : 'Sin serial';
                $correoActivacion = $asignacion->correo_activacion ?: 'N/A';

                LicenciaHistorial::create([
                    'fecha' => now(),
                    'usuario_id' => Auth::id(),
                    'accion' => 'Asignación de licencia',
                    'licencia_nombre' => $licenciaNombre,
                    'funcionario_nombre' => $funcionarioNombre,
                    'equipo_placa' => $equipoPlaca,
                    'observacion' => "Licencia asignada al equipo y funcionario. Serial: {$serialStr}. Correo de activación: {$correoActivacion}.",
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        return redirect()->route('licencia-asignaciones.index')->with('success', 'Asignación registrada exitosamente.');
    }

    public function show(LicenciaAsignacion $licencia_asignacion)
    {
        // En laravel los parametros de ruta resource pueden nombrarse 'licencia_asignacion' o 'licencia_asignacione'
        // El framework a veces quita la 's' final, así que 'licencia_asignacion' es el estándar.
        $licencia_asignacion->load(['licencia', 'equipo', 'funcionario', 'serial']);
        return view('licencias_asignaciones.show', compact('licencia_asignacion'));
    }

    public function edit(LicenciaAsignacion $licencia_asignacion)
    {
        $licencias = Licencia::where('estado', 'Activa')
            ->withCount([
                'asignaciones as cupos_asignados_count' => function ($q) {
                    $q->where('estado', 'Activa');
                },
            ])
            ->get();
        $equipos = Equipo::with('usuarioAsignado:id,equipo_id,cedula')
            ->select('id', 'nombre_equipo', 'activo_fijo', 'serial')
            ->where('estado_operativo', 'activo')
            ->orderBy('nombre_equipo')
            ->get();
        $funcionarios = Funcionario::select('id', 'nombres', 'apellidos', 'identificacion')
            ->where('estado', 'activo')
            ->get()
            ->sortBy('nombres');

        return view('licencias_asignaciones.edit', compact('licencia_asignacion', 'licencias', 'equipos', 'funcionarios'));
    }

    public function update(UpdateLicenciaAsignacionRequest $request, LicenciaAsignacion $licencia_asignacion)
    {
        $licencia = Licencia::findOrFail($licencia_asignacion->licencia_id);
        
        // Validación de fechas
        if ($request->fecha_vencimiento && $licencia->fecha_vencimiento) {
            $fechaVencimientoReq = \Carbon\Carbon::parse($request->fecha_vencimiento);
            if ($fechaVencimientoReq->gt($licencia->fecha_vencimiento)) {
                return back()->withInput()->withErrors(['fecha_vencimiento' => 'La fecha de vencimiento de la asignación no puede ser superior al vencimiento de la licencia (' . $licencia->fecha_vencimiento->format('d/m/Y') . ').']);
            }
        }

        $viejoEstado = $licencia_asignacion->estado;
        $licencia_asignacion->update($request->validated() + ['updated_by' => Auth::id()]);

        $accion = 'Actualización de asignación';
        if ($viejoEstado != $request->estado) {
            $accion = "Cambio de estado a " . $request->estado;
        }

        $licencia_asignacion->load(['licencia', 'equipo', 'funcionario', 'serial']);
        
        $licenciaNombre = $licencia_asignacion->licencia ? $licencia_asignacion->licencia->nombre : 'Sin licencia';
        $equipoPlaca = $licencia_asignacion->equipo ? $licencia_asignacion->equipo->placa : 'N/A';
        $funcionarioNombre = $licencia_asignacion->funcionario ? $licencia_asignacion->funcionario->nombre_completo : 'N/A';
        $serialStr = $licencia_asignacion->serial ? $licencia_asignacion->serial->serial : 'Sin serial';
        $correoActivacion = $licencia_asignacion->correo_activacion ?: 'N/A';

        LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'licencia_nombre' => $licenciaNombre,
            'funcionario_nombre' => $funcionarioNombre,
            'equipo_placa' => $equipoPlaca,
            'observacion' => "Asignación actualizada. Serial: {$serialStr}. Correo de activación: {$correoActivacion}.",
        ]);

        return redirect()->route('licencia-asignaciones.index')->with('success', 'Asignación actualizada exitosamente.');
    }

    public function destroy(LicenciaAsignacion $licencia_asignacion)
    {
        $licenciaNombre = $licencia_asignacion->licencia ? $licencia_asignacion->licencia->nombre : 'Sin licencia';
        $equipoPlaca = $licencia_asignacion->equipo ? $licencia_asignacion->equipo->placa : 'N/A';
        $funcionarioNombre = $licencia_asignacion->funcionario ? $licencia_asignacion->funcionario->nombre_completo : 'N/A';
        
        $serialInfo = 'Sin serial';
        
        if ($licencia_asignacion->licencia_serial_id) {
            $serial = \App\Models\LicenciaSerial::find($licencia_asignacion->licencia_serial_id);
            if ($serial) {
                $serial->estado = 'Disponible';
                $serial->save();
                $serialInfo = $serial->serial;
            }
        }

        LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => Auth::id(),
            'accion' => 'Retiro de licencia',
            'licencia_nombre' => $licenciaNombre,
            'funcionario_nombre' => $funcionarioNombre,
            'equipo_placa' => $equipoPlaca,
            'observacion' => "La asignación de licencia fue eliminada. El serial ({$serialInfo}) ha sido liberado.",
        ]);

        $licencia_asignacion->delete();

        return redirect()->route('licencia-asignaciones.index')->with('success', 'Asignación eliminada exitosamente y serial liberado.');
    }
}
