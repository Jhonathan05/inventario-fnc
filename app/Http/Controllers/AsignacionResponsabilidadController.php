<?php

namespace App\Http\Controllers;

use App\Models\AsignacionResponsabilidad;
use App\Models\Equipo;
use App\Services\HistorialService;
use App\Http\Requests\StoreAsignacionResponsabilidadRequest;
use App\Http\Requests\UpdateAsignacionResponsabilidadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AsignacionResponsabilidadController extends Controller
{
    public function store(StoreAsignacionResponsabilidadRequest $request, Equipo $equipo, HistorialService $historialService)
    {
        DB::transaction(function () use ($request, $equipo, $historialService) {
            $asignacion = AsignacionResponsabilidad::create(array_merge(
                $request->validated(),
                [
                    'equipo_id' => $equipo->id,
                    'user_id' => Auth::id(),
                    'estado' => 'activa',
                ]
            ));

            // Consumir autorización
            $autorizacion = \App\Models\AutorizacionActivo::query()
                ->where('cedula', $asignacion->responsable_cedula)
                ->where('estado', \App\Models\AutorizacionActivo::ESTADO_CARGADA)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($autorizacion) {
                $autorizacion->update([
                    'estado' => \App\Models\AutorizacionActivo::ESTADO_CONSUMIDA,
                    'equipo_id' => $equipo->id,
                    // No hay 'asignacion_id' en autorizaciones_activos para Responsabilidad
                    // porque es otra tabla, guardamos un log en consumida_en
                    'consumida_en' => now(),
                    'consumida_por_user_id' => Auth::id(),
                ]);
            }

            $obsText = $request->observaciones ? " | Observaciones: {$request->observaciones}" : "";
            $desc = "Nueva Asignación Bajo Responsabilidad. Responsable: {$asignacion->responsable_nombre} | Usuario Temp: {$asignacion->nombre_usuario} | Proyecto: " . ($asignacion->proyecto ?? 'N/A') . $obsText;
            
            $historialService->registrarCambio(
                $equipo,
                'cambio_responsable',
                null,
                $asignacion->nombre_usuario,
                $desc,
                Auth::user()
            );
        });

        return back()->with('success', 'Asignación bajo responsabilidad registrada correctamente.');
    }

    public function update(UpdateAsignacionResponsabilidadRequest $request, Equipo $equipo, AsignacionResponsabilidad $asignacion, HistorialService $historialService)
    {
        DB::transaction(function () use ($request, $equipo, $asignacion, $historialService) {
            $nuevosDatos = $request->validated();
            $datosViejos = $asignacion->only(array_keys($nuevosDatos));

            $asignacion->update($nuevosDatos);

            $cambios = [];
            foreach ($nuevosDatos as $campo => $nuevoValor) {
                $viejoValor = $datosViejos[$campo] ?? null;
                if ($viejoValor !== $nuevoValor && $campo !== 'observaciones') {
                    $cambios[] = "{$campo}: {$viejoValor} ➔ {$nuevoValor}";
                }
            }

            if (count($cambios) > 0 || ($datosViejos['observaciones'] ?? '') !== ($nuevosDatos['observaciones'] ?? '')) {
                $desc = count($cambios) > 0 ? "Actualización de responsabilidad: " . implode(', ', $cambios) : "Actualización de observaciones bajo responsabilidad.";
                $obsText = !empty($nuevosDatos['observaciones']) ? " | Observaciones: {$nuevosDatos['observaciones']}" : "";
                
                $historialService->registrarCambio(
                    $equipo,
                    'cambio_responsable',
                    $asignacion->nombre_usuario, // as a reference
                    $asignacion->nombre_usuario,
                    $desc . $obsText,
                    Auth::user()
                );
            }
        });

        return back()->with('success', 'Asignación bajo responsabilidad actualizada correctamente.');
    }

    public function destroy(Request $request, Equipo $equipo, AsignacionResponsabilidad $asignacion, HistorialService $historialService)
    {
        $request->validate([
            'fecha_final_real' => 'required|date',
            'motivo_finalizacion' => 'required|string|max:150',
            'observaciones_finales' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $equipo, $asignacion, $historialService) {
            $asignacion->update([
                'estado' => 'finalizada',
                'fecha_final_real' => $request->fecha_final_real,
                'motivo_finalizacion' => $request->motivo_finalizacion,
                'observaciones_finales' => $request->observaciones_finales,
                'finalizado_por' => Auth::id(),
            ]);

            // Liberar autorización
            $autorizacion = \App\Models\AutorizacionActivo::query()
                ->where('cedula', $asignacion->responsable_cedula)
                ->where('equipo_id', $equipo->id)
                ->where('estado', \App\Models\AutorizacionActivo::ESTADO_CONSUMIDA)
                ->orderBy('id', 'desc')
                ->first();

            if ($autorizacion) {
                $autorizacion->update([
                    'estado' => \App\Models\AutorizacionActivo::ESTADO_CARGADA,
                    'equipo_id' => null,
                    'consumida_en' => null,
                    'consumida_por_user_id' => null,
                ]);
            }

            $obsText = $request->observaciones_finales ? " | Obs: {$request->observaciones_finales}" : "";
            $desc = "Finalizada Asignación Bajo Responsabilidad de {$asignacion->nombre_usuario}. Responsable: {$asignacion->responsable_nombre}. Motivo: {$request->motivo_finalizacion}." . $obsText;

            $historialService->registrarCambio(
                $equipo,
                'cambio_responsable',
                $asignacion->nombre_usuario,
                null,
                $desc,
                Auth::user()
            );
        });

        return back()->with('success', 'Asignación bajo responsabilidad finalizada correctamente.');
    }
}

