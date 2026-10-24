<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Regla institucional de bodega (razón CUSTODIA del catálogo):
 * "Todo activo que ingrese a bodega estará almacenado 15 días hábiles;
 * después de este tiempo se realizará el cambio de estado a DISPONIBLE
 * para su asignación según se requiera." (Listas De Validación Equipos)
 *
 * Los activos en bodega con razón RETORNO o CUSTODIA pasan a razón
 * DISPONIBLE una vez cumplidos los 15 días hábiles desde su entrada
 * (última novedad registrada). RENOVACIÓN y DISPONIBLE no se tocan.
 *
 * Nota: días hábiles = lunes a viernes (los festivos colombianos se
 * añaden cuando se cargue el calendario institucional).
 */
class LiberarBodegaDisponibles extends Command
{
    protected $signature = 'bodega:liberar-disponibles {--dry-run : Muestra qué cambiaría sin escribir}';

    protected $description = 'Bodega: tras 15 días hábiles, RETORNO/CUSTODIA pasan a razón DISPONIBLE';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $enBodega = Equipo::query()
            ->whereNull('equipos.deleted_at')
            ->where('equipos.estado_operativo', 'almacenado')
            ->whereIn('equipos.razon_estado', ['retorno', 'custodia'])
            ->get(['id', 'nombre_equipo', 'serial', 'placa', 'razon_estado']);

        $liberados = 0;

        foreach ($enBodega as $equipo) {
            // Fecha de entrada a bodega: última novedad del activo.
            $entrada = DB::table('asignaciones')
                ->where('equipo_id', $equipo->id)
                ->max('fecha_accion');

            if ($entrada === null) {
                continue; // sin trazabilidad de entrada: no se toca
            }

            $habiles = $this->diasHabiles(Carbon::parse($entrada)->startOfDay(), $hoy);

            if ($habiles < 15) {
                continue;
            }

            $liberados++;
            $this->line(sprintf(
                '  %s %s%s · %s → DISPONIBLE (%d días hábiles)',
                $equipo->placa ?: 'sin placa',
                $equipo->serial ?: '',
                $equipo->nombre_equipo ? " · {$equipo->nombre_equipo}" : '',
                $equipo->razon_estado,
                $habiles
            ));

            if (! $this->option('dry-run')) {
                $equipo->forceFill(['razon_estado' => 'disponible'])->save();

                DB::table('historial_administrativos')->insert([
                    'equipo_id'        => $equipo->id,
                    'user_id'          => null,
                    'tipo_cambio'      => 'bodega_disponible',
                    'campo_modificado' => 'razon_estado',
                    'valor_anterior'   => $equipo->razon_estado,
                    'valor_nuevo'      => 'disponible',
                    'descripcion'      => "Regla de bodega: 15 días hábiles cumplidos (entrada {$entrada}).",
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        $this->info($this->option('dry-run')
            ? "DRY-RUN: {$liberados} activo(s) pasaría(n) a razón DISPONIBLE."
            : "✔ {$liberados} activo(s) liberado(s) a razón DISPONIBLE.");

        return self::SUCCESS;
    }

    /** Días hábiles (lun–vie) transcurridos entre dos fechas. */
    private function diasHabiles(Carbon $desde, Carbon $hasta): int
    {
        if ($hasta->lte($desde)) {
            return 0;
        }

        $dias = 0;
        $cursor = $desde->copy()->addDay();

        while ($cursor->lte($hasta)) {
            if (! $cursor->isWeekend()) {
                $dias++;
            }
            $cursor->addDay();
        }

        return $dias;
    }
}
