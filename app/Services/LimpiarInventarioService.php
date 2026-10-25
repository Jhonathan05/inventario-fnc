<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LimpiarInventarioService — reset del inventario de activos para reimportar
 * una nueva versión del CMDB (Lote B8).
 *
 * Alcance: TODO lo que depende de un activo — equipos, custodia, movimientos
 * (actas/asignaciones), correcciones, préstamos, tickets, historiales,
 * complementos, periféricos, checklists, autorizaciones, tareas y licencias
 * asignadas.
 *
 * Se MANTIENE intacto (datos sembrados / maestros):
 *   funcionarios (enlazados de nuevo al importar) · catálogos CMDB ·
 *   territorios · tipos de recurso · licencias (catálogo) · usuarios/roles ·
 *   respaldos.
 */
class LimpiarInventarioService
{
    /** Tablas dependientes de activos, en orden seguro de borrado. */
    private const TABLAS = [
        'correcciones',
        'usuario_asignados',
        'asignaciones',
        'asignaciones_responsabilidad',
        'autorizaciones_activos',
        'prestamos',
        'tickets',
        'tareas',
        'historial_tecnicos',
        'historial_administrativos',
        'activo_complementos',
        'perifericos',
        'checklists',
        'licencia_asignaciones',
        'actas',
    ];

    /** @return array<string, int> filas actuales por tabla (para el modal). */
    public function resumen(): array
    {
        $conteo = ['equipos' => DB::table('equipos')->count()];

        foreach (self::TABLAS as $tabla) {
            if (Schema::hasTable($tabla)) {
                $conteo[$tabla] = DB::table($tabla)->count();
            }
        }

        // Los activos en papelera también se purgan
        $conteo['equipos_en_papelera'] = DB::table('equipos')->whereNotNull('deleted_at')->count();

        return $conteo;
    }

    /**
     * Ejecuta la limpieza en una transacción y devuelve lo borrado.
     *
     * @return array{borradas: array<string, int>, equipos: int}
     */
    public function limpiar(): array
    {
        $borradas = [];

        // Borrado en transacción (sin DDL: MySQL hace commit implícito con ALTER)
        DB::transaction(function () use (&$borradas) {
            foreach (self::TABLAS as $tabla) {
                if (! Schema::hasTable($tabla)) {
                    continue;
                }
                $borradas[$tabla] = DB::table($tabla)->count();
                DB::table($tabla)->delete();
            }

            $borradas['equipos'] = DB::table('equipos')->count();
            DB::table('equipos')->delete();
        });

        // Fuera de la transacción: reiniciar el autoincrement de activos.
        DB::statement('ALTER TABLE equipos AUTO_INCREMENT = 1');

        return ['borradas' => $borradas, 'equipos' => $borradas['equipos']];
    }
}
