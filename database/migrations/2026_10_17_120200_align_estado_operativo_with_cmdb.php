<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea el estado operativo con el catálogo oficial del CMDB
 * (hoja "Listas De Validación Equipos"):
 *
 *   almacenado, disponible, transito, en_operacion, asignacion_responsable,
 *   prestamo, reparacion (antes "mantenimiento"), baja, procesar_baja,
 *   hurto, pendiente_estado
 *
 * Y agrega a `equipos` los campos CMDB que el acta de novedad no exige
 * pero el formato sí: estado de uso, administrado/controlado, tipo de
 * propiedad, checklist responsable TI, orden de remisión y observaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('estado_operativo', 30)->default('en_operacion')->change();
            $table->string('estado_uso', 100)->nullable();
            $table->string('administrado', 20)->nullable(); // administrado | controlado
            $table->string('tipo_propiedad', 100)->nullable();
            $table->string('checklist_responsable', 150)->nullable();
            $table->string('orden_remision', 100)->nullable();
            $table->text('observaciones')->nullable();
        });

        DB::table('equipos')->whereIn('estado_operativo', ['activo', 'asignado'])
            ->update(['estado_operativo' => 'en_operacion']);
        DB::table('equipos')->where('estado_operativo', 'mantenimiento')
            ->update(['estado_operativo' => 'reparacion']);
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['estado_uso', 'administrado', 'tipo_propiedad', 'checklist_responsable', 'orden_remision', 'observaciones']);
        });
        DB::table('equipos')->where('estado_operativo', 'en_operacion')->update(['estado_operativo' => 'asignado']);
        DB::table('equipos')->where('estado_operativo', 'reparacion')->update(['estado_operativo' => 'mantenimiento']);
        DB::statement("ALTER TABLE equipos MODIFY estado_operativo ENUM('activo','mantenimiento','baja','asignado','disponible') NOT NULL DEFAULT 'activo'");
    }
};
