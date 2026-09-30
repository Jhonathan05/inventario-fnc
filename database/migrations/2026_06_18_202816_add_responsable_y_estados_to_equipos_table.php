<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('responsable_cedula', 20)->nullable()->after('tiempo_uso');
            $table->string('responsable_nombre', 150)->nullable()->after('responsable_cedula');
            $table->string('responsable_cargo', 100)->nullable()->after('responsable_nombre');
            $table->string('responsable_ciudad', 100)->nullable()->after('responsable_cargo');
            $table->string('responsable_area', 100)->nullable()->after('responsable_ciudad');
            $table->string('responsable_tipo_recurso', 100)->nullable()->after('responsable_area');
            $table->date('fecha_inicio_responsable')->nullable()->after('responsable_tipo_recurso');
            $table->date('fecha_fin_responsable')->nullable()->after('fecha_inicio_responsable');
        });

        // Alterar el ENUM para añadir 'asignado' y 'disponible'
        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja', 'asignado', 'disponible') NOT NULL DEFAULT 'activo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn([
                'responsable_cedula',
                'responsable_nombre',
                'responsable_cargo',
                'responsable_ciudad',
                'responsable_area',
                'responsable_tipo_recurso',
                'fecha_inicio_responsable',
                'fecha_fin_responsable',
            ]);
        });
        
        // Revertir el ENUM si es posible (en MySQL es seguro mientras no hayan datos con los nuevos valores)
        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja') NOT NULL");
    }
};
