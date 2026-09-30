<?php

namespace App\Observers;

use App\Models\ActivoComplemento;
use App\Models\HistorialComplemento;
use Illuminate\Support\Facades\Auth;

class ActivoComplementoObserver
{
    /**
     * Handle the ActivoComplemento "created" event.
     */
    public function created(ActivoComplemento $complemento): void
    {
        HistorialComplemento::create([
            'complemento_id' => $complemento->id,
            'evento' => 'CREADO',
            'usuario_id' => Auth::id() ?? 1,
            'fecha' => now(),
            'activo_destino_id' => $complemento->equipo_id,
            'estado_nuevo' => $complemento->estado,
            'valor_nuevo' => json_encode($complemento->only(['marca', 'modelo', 'serial', 'cantidad', 'observaciones'])),
            'observacion' => 'Complemento registrado en el sistema',
        ]);
        
        if ($complemento->equipo_id) {
            HistorialComplemento::create([
                'complemento_id' => $complemento->id,
                'evento' => 'ASOCIADO',
                'usuario_id' => Auth::id() ?? 1,
                'fecha' => now(),
                'activo_destino_id' => $complemento->equipo_id,
                'estado_nuevo' => $complemento->estado,
                'observacion' => 'Asociado al activo al momento de su creación',
            ]);
        }
    }

    /**
     * Handle the ActivoComplemento "updated" event.
     */
    public function updated(ActivoComplemento $complemento): void
    {
        $cambios = $complemento->getDirty();
        $original = $complemento->getOriginal();

        // 1. Cambio de Asociación (Transferido, Liberado, Reasignado)
        if (array_key_exists('equipo_id', $cambios)) {
            $viejo_equipo = $original['equipo_id'] ?? null;
            $nuevo_equipo = $cambios['equipo_id'] ?? null;

            if ($viejo_equipo && $nuevo_equipo) {
                // Transferido
                HistorialComplemento::create([
                    'complemento_id' => $complemento->id,
                    'evento' => 'TRANSFERIDO',
                    'usuario_id' => Auth::id() ?? 1,
                    'fecha' => now(),
                    'activo_origen_id' => $viejo_equipo,
                    'activo_destino_id' => $nuevo_equipo,
                    'estado_anterior' => $original['estado'] ?? null,
                    'estado_nuevo' => $complemento->estado,
                    'observacion' => 'Complemento transferido de activo',
                ]);
            } elseif ($viejo_equipo && !$nuevo_equipo) {
                // Liberado
                HistorialComplemento::create([
                    'complemento_id' => $complemento->id,
                    'evento' => 'LIBERADO',
                    'usuario_id' => Auth::id() ?? 1,
                    'fecha' => now(),
                    'activo_origen_id' => $viejo_equipo,
                    'estado_anterior' => $original['estado'] ?? null,
                    'estado_nuevo' => $complemento->estado,
                    'observacion' => 'Complemento liberado del activo',
                ]);
            } elseif (!$viejo_equipo && $nuevo_equipo) {
                // Reasignado o Asociado (posterior)
                HistorialComplemento::create([
                    'complemento_id' => $complemento->id,
                    'evento' => 'REASIGNADO',
                    'usuario_id' => Auth::id() ?? 1,
                    'fecha' => now(),
                    'activo_destino_id' => $nuevo_equipo,
                    'estado_anterior' => $original['estado'] ?? null,
                    'estado_nuevo' => $complemento->estado,
                    'observacion' => 'Complemento asociado/reasignado a un activo',
                ]);
            }
        }

        // 2. Cambio de Estado
        if (array_key_exists('estado', $cambios)) {
            HistorialComplemento::create([
                'complemento_id' => $complemento->id,
                'evento' => 'ESTADO_CAMBIADO',
                'usuario_id' => Auth::id() ?? 1,
                'fecha' => now(),
                'activo_origen_id' => $complemento->equipo_id,
                'activo_destino_id' => $complemento->equipo_id,
                'estado_anterior' => $original['estado'],
                'estado_nuevo' => $cambios['estado'],
                'observacion' => 'Estado cambiado de ' . $original['estado'] . ' a ' . $cambios['estado'],
            ]);
        }

        // 3. Otros campos editados (Serial, Marca, Modelo, Cantidad)
        $campos_especificos = [
            'serial' => 'SERIAL_CAMBIADO',
            'marca' => 'MARCA_CAMBIADA',
            'modelo' => 'MODELO_CAMBIADO',
            'cantidad' => 'CANTIDAD_CAMBIADA'
        ];

        foreach ($campos_especificos as $campo => $evento) {
            if (array_key_exists($campo, $cambios)) {
                HistorialComplemento::create([
                    'complemento_id' => $complemento->id,
                    'evento' => $evento,
                    'usuario_id' => Auth::id() ?? 1,
                    'fecha' => now(),
                    'campo_modificado' => $campo,
                    'valor_anterior' => (string) ($original[$campo] ?? ''),
                    'valor_nuevo' => (string) ($cambios[$campo] ?? ''),
                    'observacion' => "Campo {$campo} actualizado",
                ]);
            }
        }

        // 4. Edición genérica si no es un evento específico
        $excluidos = ['equipo_id', 'estado', 'serial', 'marca', 'modelo', 'cantidad', 'updated_at', 'deleted_at'];
        $otros_cambios = array_diff_key($cambios, array_flip($excluidos));

        if (!empty($otros_cambios) && empty(array_intersect_key($cambios, array_flip(['equipo_id', 'estado', 'serial', 'marca', 'modelo', 'cantidad'])))) {
            HistorialComplemento::create([
                'complemento_id' => $complemento->id,
                'evento' => 'EDITADO',
                'usuario_id' => Auth::id() ?? 1,
                'fecha' => now(),
                'informacion_adicional' => $otros_cambios,
                'observacion' => 'Información del complemento actualizada',
            ]);
        }
    }

    /**
     * Handle the ActivoComplemento "deleted" event.
     */
    public function deleted(ActivoComplemento $complemento): void
    {
        HistorialComplemento::create([
            'complemento_id' => $complemento->id,
            'evento' => 'DADO_DE_BAJA',
            'usuario_id' => Auth::id() ?? 1,
            'fecha' => now(),
            'activo_origen_id' => $complemento->equipo_id,
            'estado_anterior' => $complemento->estado,
            'estado_nuevo' => 'Dado de baja',
            'observacion' => 'El complemento fue dado de baja / eliminado',
        ]);
    }

    /**
     * Handle the ActivoComplemento "restored" event.
     */
    public function restored(ActivoComplemento $complemento): void
    {
        HistorialComplemento::create([
            'complemento_id' => $complemento->id,
            'evento' => 'RESTAURADO',
            'usuario_id' => Auth::id() ?? 1,
            'fecha' => now(),
            'activo_destino_id' => $complemento->equipo_id,
            'estado_nuevo' => $complemento->estado,
            'observacion' => 'Complemento restaurado en el sistema',
        ]);
    }
}
