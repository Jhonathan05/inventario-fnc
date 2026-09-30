<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->index('estado_operativo', 'idx_equipos_estado_operativo');
            $table->index('nombre_equipo', 'idx_equipos_nombre_equipo');
            $table->index('serial', 'idx_equipos_serial');
            $table->index('created_at', 'idx_equipos_created_at');
        });

        Schema::table('funcionarios', function (Blueprint $table) {
            $table->index('estado', 'idx_funcionarios_estado');
            $table->index('nombres', 'idx_funcionarios_nombres');
            $table->index('identificacion', 'idx_funcionarios_identificacion');
        });

        Schema::table('licencias', function (Blueprint $table) {
            $table->index('estado', 'idx_licencias_estado');
            $table->index('fecha_vencimiento', 'idx_licencias_fecha_vencimiento');
        });

        Schema::table('historial_tecnicos', function (Blueprint $table) {
            $table->index(['equipo_id', 'fecha_evento'], 'idx_historial_tec_equipo_fecha');
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->index(['equipo_id', 'fecha_accion'], 'idx_asignaciones_equipo_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex('idx_equipos_estado_operativo');
            $table->dropIndex('idx_equipos_nombre_equipo');
            $table->dropIndex('idx_equipos_serial');
            $table->dropIndex('idx_equipos_created_at');
        });

        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropIndex('idx_funcionarios_estado');
            $table->dropIndex('idx_funcionarios_nombres');
            $table->dropIndex('idx_funcionarios_identificacion');
        });

        Schema::table('licencias', function (Blueprint $table) {
            $table->dropIndex('idx_licencias_estado');
            $table->dropIndex('idx_licencias_fecha_vencimiento');
        });

        Schema::table('historial_tecnicos', function (Blueprint $table) {
            $table->dropIndex('idx_historial_tec_equipo_fecha');
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropIndex('idx_asignaciones_equipo_fecha');
        });
    }
};
