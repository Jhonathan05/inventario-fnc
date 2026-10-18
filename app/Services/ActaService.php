<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Registro de actas de novedad de activos (fase 2, flujo 1).
 *
 * Un acta agrupa N activos sin relación entre sí, valida en transacción que
 * todos estén en el estado requerido por el tipo de movimiento (con bloqueo
 * de fila para que dos actas no se lleven el mismo activo) y deja todos los
 * activos en el estado resultante del movimiento. En la misma transacción
 * actualiza los campos CMDB del activo que el movimiento puede cambiar:
 * un solo movimiento rellena las dos fuentes de información.
 */
class ActaService
{
    /**
     * Registra un acta con sus ítems y el cambio de estado de los activos.
     *
     * @param  array<string, mixed>  $datos  Campos del acta (Acta::fillable).
     * @param  array<int, mixed>     $fichasPorEquipo  [equipo_id => [campo CMDB => valor]].
     * @param  \Illuminate\Support\Collection<int, Equipo>|array<int, Equipo>  $equipos
     */
    public function registrar(array $datos, $equipos, array $fichasPorEquipo = [], ?User $usuario = null): Acta
    {
        $tipo = (string) ($datos['tipo_accion'] ?? '');
        $regla = Acta::REGLAS_MOVIMIENTO[$tipo] ?? null;

        if ($regla === null) {
            throw new RuntimeException("Tipo de movimiento no válido: {$tipo}");
        }

        $equipos = collect($equipos);

        if ($equipos->isEmpty()) {
            throw new RuntimeException('El acta debe incluir al menos un activo.');
        }

        return DB::transaction(function () use ($datos, $regla, $equipos, $fichasPorEquipo, $usuario, $tipo) {
            // Bloquear los activos y validar el estado requerido (todo-o-nada)
            $bloqueados = Equipo::query()
                ->whereIn('id', $equipos->pluck('id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fueraDeEstado = $bloqueados
                ->filter(fn (Equipo $equipo) => ! in_array($equipo->estado_operativo, $regla['requiere'], true))
                ->map(fn (Equipo $equipo) => sprintf('%s (%s)', $equipo->nombre_equipo ?: $equipo->serial, $equipo->estado_operativo));

            if ($fueraDeEstado->isNotEmpty()) {
                throw new RuntimeException('Activos que no están en el estado requerido por el movimiento: ' . $fueraDeEstado->implode(', '));
            }

            $estadoFinal = $this->estadoFinal($tipo, $regla, $datos, $bloqueados);

            $acta = Acta::create(array_merge($datos, [
                'user_id' => $usuario?->id,
                'fecha_accion' => $datos['fecha_accion'] ?? now(),
            ]));

            foreach ($bloqueados as $equipo) {
                // Ítem del acta: hereda el snapshot del responsable para que el
                // detalle y los documentos legacy sigan funcionando.
                $acta->items()->create([
                    'equipo_id' => $equipo->id,
                    'tipo_accion' => $tipo,
                    'fecha_accion' => $acta->fecha_accion,
                    'usuario_nombre' => $acta->usuario_nombre,
                    'usuario_cedula' => $acta->usuario_cedula,
                    'usuario_empresa_propietaria' => $acta->usuario_empresa_propietaria,
                    'usuario_dependencia' => $acta->usuario_dependencia,
                    'usuario_fuente_recurso' => $acta->usuario_fuente_recurso,
                    'usuario_empresa_funcionario' => $acta->usuario_empresa_funcionario,
                    'usuario_tipo_vinculacion' => $acta->usuario_tipo_vinculacion,
                    'usuario_shortname' => $acta->usuario_shortname,
                    'usuario_departamento' => $acta->usuario_departamento,
                    'usuario_ciudad' => $acta->usuario_ciudad,
                    'usuario_cargo' => $acta->usuario_cargo,
                    'usuario_area' => $acta->usuario_area,
                    'usuario_piso' => $acta->usuario_piso,
                    'usuario_distrito' => $acta->usuario_distrito,
                    'usuario_seccional' => $acta->usuario_seccional,
                    'motivo' => $acta->observaciones,
                    'entregado_por' => $acta->entregador_nombre,
                    'user_id' => $acta->user_id,
                ]);

                // Ficha CMDB del activo + estado resultante del movimiento
                $ficha = array_merge($fichasPorEquipo[$equipo->id] ?? []);
                $cambios = [];
                foreach (['estado_uso', 'administrado', 'tipo_propiedad', 'checklist_responsable', 'orden_remision', 'observaciones'] as $campo) {
                    if (array_key_exists($campo, $ficha)) {
                        $cambios[$campo] = $ficha[$campo];
                    }
                }
                if ($estadoFinal !== null) {
                    $cambios['estado_operativo'] = $estadoFinal;
                }
                if (! empty($cambios)) {
                    $equipo->update($cambios);
                }
            }

            return $acta;
        });
    }

    /**
     * Estado resultante del movimiento para todos los activos del acta.
     */
    private function estadoFinal(string $tipo, array $regla, array $datos, Collection $bloqueados): ?string
    {
        if ($regla['deja'] === null) {
            if ($tipo === Acta::TIPO_TRASLADO || $tipo === Acta::TIPO_CAMBIO_RESPONSABLE) {
                return null; // mantienen el estado
            }

            // Reparación es bidireccional: si el activo está en reparación es la
            // salida (vuelve al estado de su responsable); si no, es la entrada.
            $enReparacion = $bloqueados->contains(fn (Equipo $e) => $e->estado_operativo === 'reparacion');

            return $enReparacion ? ($datos['destino'] ?? 'en_operacion') : 'reparacion';
        }

        $destino = $datos['destino'] ?? null;

        if ($destino !== null) {
            if (! in_array($destino, $regla['deja'], true)) {
                throw new RuntimeException("Destino no válido para {$tipo}: {$destino}");
            }

            return $destino;
        }

        return $regla['deja'][0];
    }
}
