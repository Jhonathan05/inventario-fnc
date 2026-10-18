<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las asignaciones pasan a ser ítems del acta de novedad y adoptan el
 * catálogo de movimientos v3: inventario_fisico, traslado,
 * cambio_responsable, retiro_definitivo, prestamo y reparacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->foreignId('acta_id')->nullable()->after('id')
                ->constrained('actas')->nullOnDelete();
        });

        // tipo_accion: enum legacy -> varchar con el catálogo v3
        DB::statement("ALTER TABLE asignaciones MODIFY tipo_accion VARCHAR(40) NOT NULL");

        $mapa = [
            'asignacion' => 'prestamo', // el tipo legacy "asignacion" se etiquetaba "Préstamo"
            'reemplazo' => 'cambio_responsable',
            'retiro' => 'retiro_definitivo',
            'mantenimiento' => 'reparacion',
            'restauracion' => 'reparacion', // salida de reparación: mismo movimiento
            'baja' => 'retiro_definitivo',
            'inventario_fisico' => 'inventario_fisico',
        ];
        foreach ($mapa as $viejo => $nuevo) {
            DB::table('asignaciones')->where('tipo_accion', $viejo)->update(['tipo_accion' => $nuevo]);
        }

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->index('acta_id');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropIndex(['acta_id']);
            $table->dropConstrainedForeignId('acta_id');
        });
        DB::statement("ALTER TABLE asignaciones MODIFY tipo_accion ENUM('asignacion','reemplazo','retiro','mantenimiento','baja','restauracion','inventario_fisico') NOT NULL");
    }
};
