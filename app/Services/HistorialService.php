<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\HistorialAdministrativo;
use App\Models\User;
use Illuminate\Support\Collection;

class HistorialService
{
    /**
     * Registra un cambio administrativo en el historial.
     */
    public function registrarCambio(
        Equipo  $equipo,
        string  $tipoCambio,
        mixed   $valorAnterior,
        mixed   $valorNuevo,
        ?string $descripcion = null,
        ?User   $user = null,
        ?string $campoModificado = null
    ): HistorialAdministrativo {
        return HistorialAdministrativo::create([
            'equipo_id'        => $equipo->id,
            'user_id'          => $user?->id ?? auth()->id(),
            'tipo_cambio'      => $tipoCambio,
            'campo_modificado' => $campoModificado,
            'valor_anterior'   => is_array($valorAnterior) ? json_encode($valorAnterior) : $valorAnterior,
            'valor_nuevo'      => is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo,
            'descripcion'      => $descripcion,
        ]);
    }

    /**
     * Registra múltiples cambios de campos comparando arrays.
     * Útil al editar un equipo con varios campos cambiados.
     */
    public function registrarCambiosCampos(
        Equipo  $equipo,
        array   $anterior,
        array   $nuevo,
        ?User   $user = null
    ): void {
        $camposAuditados = [
            'serial'           => 'cambio_serial',
            'activo_fijo'      => 'cambio_activo',
            'estado_operativo' => 'cambio_estado',
            'marca'            => 'edicion',
            'modelo'           => 'edicion',
            'nombre_equipo'    => 'edicion',
            'procesador'       => 'edicion',
            'ram'              => 'edicion',
            'disco'            => 'edicion',
            'sistema_operativo'=> 'edicion',
        ];

        foreach ($camposAuditados as $campo => $tipoCambio) {
            if (!array_key_exists($campo, $anterior) || !array_key_exists($campo, $nuevo)) {
                continue;
            }
            if ($anterior[$campo] !== $nuevo[$campo]) {
                $this->registrarCambio(
                    $equipo,
                    $tipoCambio,
                    $anterior[$campo],
                    $nuevo[$campo],
                    "Campo '{$campo}' modificado",
                    $user,
                    $campo
                );
            }
        }
    }

    /**
     * Obtiene la línea de tiempo combinada del equipo
     * (asignaciones + historial técnico + historial administrativo).
     * Retorna colección ordenada de más reciente a más antigua.
     */
    public function obtenerLineaDeTiempo(Equipo $equipo): Collection
    {
        $equipo->load([
            'asignaciones.registradoPor',
            'historialTecnico.registradoPor',
            'historialAdministrativo.realizadoPor',
        ]);

        $eventos = collect();

        // Asignaciones
        foreach ($equipo->asignaciones as $asig) {
            $eventos->push([
                'tipo'         => 'asignacion',
                'subtipo'      => $asig->tipo_accion,
                'titulo'       => $asig->tipo_accion_label,
                'descripcion'  => $asig->usuario_nombre
                    ? "Usuario: {$asig->usuario_nombre} — CC: {$asig->usuario_cedula}"
                    : $asig->motivo,
                'fecha'        => $asig->fecha_accion,
                'responsable'  => $asig->registradoPor?->name,
                'color'        => $asig->tipo_accion_color,
                'icono'        => 'bi-person-fill',
                'modelo'       => $asig,
            ]);
        }

        // Historial técnico
        foreach ($equipo->historialTecnico as $ht) {
            $eventos->push([
                'tipo'         => 'tecnico',
                'subtipo'      => $ht->tipo_evento,
                'titulo'       => $ht->tipo_evento_label,
                'descripcion'  => $ht->descripcion,
                'fecha'        => $ht->fecha_evento,
                'responsable'  => $ht->usuario_responsable,
                'color'        => $ht->tipo_evento_color,
                'icono'        => $ht->tipo_evento_icono,
                'modelo'       => $ht,
            ]);
        }

        // Historial administrativo
        foreach ($equipo->historialAdministrativo as $ha) {
            $eventos->push([
                'tipo'         => 'administrativo',
                'subtipo'      => $ha->tipo_cambio,
                'titulo'       => $ha->tipo_cambio_label,
                'descripcion'  => $ha->descripcion,
                'fecha'        => $ha->created_at,
                'responsable'  => $ha->realizadoPor?->name,
                'color'        => $ha->tipo_cambio_color,
                'icono'        => 'bi-shield-check',
                'modelo'       => $ha,
            ]);
        }

        return $eventos->sortByDesc('fecha')->values();
    }

    public function registrarTransferenciaComplemento(
        Equipo $equipoOrigen,
        Equipo $equipoDestino,
        \App\Models\ActivoComplemento $complemento,
        ?User $user = null,
        ?string $observaciones = null
    ): void {
        $nombreComplemento = $complemento->nombre;

        // Historial origen
        $this->registrarCambio(
            $equipoOrigen,
            'transferencia_complemento',
            $equipoOrigen->id,
            $equipoDestino->id,
            "Complemento '{$nombreComplemento}' transferido al activo {$equipoDestino->activo_fijo} (ID: {$equipoDestino->id}). " . $observaciones,
            $user
        );

        // Historial destino
        $this->registrarCambio(
            $equipoDestino,
            'transferencia_complemento',
            $equipoOrigen->id,
            $equipoDestino->id,
            "Complemento '{$nombreComplemento}' recibido desde activo {$equipoOrigen->activo_fijo} (ID: {$equipoOrigen->id}). " . $observaciones,
            $user
        );
    }
}
