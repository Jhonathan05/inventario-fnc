<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lote B1 — catálogo real (6 estados + 13 razones) en el flujo.
 *
 *  - `asignaciones`: razón anterior/resultante del movimiento (reversión
 *    simétrica al anular, complementa estado_anterior/estado_resultante).
 *  - Backfill de estados legacy → catálogo real del CMDB:
 *      disponible → almacenado · prestamo → en_operacion+PRÉSTAMO
 *      reparacion → almacenado+ALISTAMIENTO/SOPORTE · asignacion_responsable → en_operacion
 *      procesar_baja → baja+PROCESAR BAJA · activo/asignado → en_operacion
 *      mantenimiento → almacenado+ALISTAMIENTO/SOPORTE
 *  - `equipos.razon_estado` pasa a guardar el CÓDIGO del catálogo
 *    (se normaliza el texto crudo del importador).
 *  - `actas.destino` de retiro pasa a ser razón ('disponible' legacy →
 *    'disponible' razón de bodega, ya soportado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->string('razon_anterior', 40)->nullable()->after('estado_resultante');
            $table->string('razon_resultante', 40)->nullable()->after('razon_anterior');
        });

        // ── Backfill de estados legacy (catálogo real) ──────────────────────
        $mapaEstadoRazon = [
            'disponible'           => ['almacenado', 'disponible'],
            'prestamo'             => ['en_operacion', 'prestamo'],
            'reparacion'           => ['almacenado', 'alistamiento_soporte'],
            'mantenimiento'       => ['almacenado', 'alistamiento_soporte'],
            'asignacion_responsable' => ['en_operacion', 'asignacion_responsable'],
            'procesar_baja'        => ['baja', 'procesar_baja'],
            'activo'               => ['en_operacion', 'asignacion_responsable'],
            'asignado'             => ['en_operacion', 'asignacion_responsable'],
        ];

        foreach ($mapaEstadoRazon as $legacy => [$estado, $razon]) {
            DB::table('equipos')
                ->where('estado_operativo', $legacy)
                ->update(['estado_operativo' => $estado]);

            // Razón sólo si quedó vacía (respeta la del archivo)
            DB::table('equipos')
                ->whereNull('razon_estado')
                ->where('estado_operativo', $estado)
                ->update(['razon_estado' => $razon]);
        }

        // Razones por defecto para estados del catálogo sin razón
        foreach (['almacenado' => 'disponible', 'transito' => 'despacho', 'baja' => 'baja',
                  'hurto' => 'hurto_perdida', 'pendiente_estado' => null] as $estado => $razon) {
            if ($razon === null) continue;
            DB::table('equipos')
                ->whereNull('razon_estado')
                ->where('estado_operativo', $estado)
                ->update(['razon_estado' => $razon]);
        }
        DB::table('equipos')
            ->whereNull('razon_estado')
            ->where('estado_operativo', 'en_operacion')
            ->update(['razon_estado' => 'asignacion_responsable']);

        // Normalizar razones en texto crudo del importador → código
        $razones = DB::table('catalogo_razones')->get();
        foreach ($razones as $r) {
            DB::table('equipos')
                ->where('razon_estado', $r->nombre)
                ->update(['razon_estado' => $r->codigo]);
        }
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropColumn(['razon_anterior', 'razon_resultante']);
        });
        // El backfill de datos no se revierte (pérdida de información);
        // los estados legacy ya no existen en el catálogo real.
    }
};
