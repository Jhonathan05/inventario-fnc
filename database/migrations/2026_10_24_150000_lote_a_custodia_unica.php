<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lote A — "Custodia única y acta que sí asigna" (plan §11).
 *
 * La custodia actual del activo (usuario_asignados) pasa a ser una proyección
 * derivada escrita únicamente por CustodiaService dentro de la transacción
 * del acta; asignaciones es el historial inmutable de movimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Historial de movimientos ─────────────────────────────────────────
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->string('estado_anterior', 30)->nullable()->after('equipo_id');
            $table->string('estado_resultante', 30)->nullable()->after('estado_anterior');
            $table->boolean('vigente')->default(false)->after('estado_resultante');
            $table->timestamp('fecha_fin')->nullable()->after('fecha_accion');
            $table->string('origen', 20)->default('acta')->after('fecha_fin');
            $table->string('modalidad', 20)->nullable()->after('origen'); // asignado|custodia_fisica
            $table->string('codigo_personal', 30)->nullable()->after('usuario_seccional');
            $table->unsignedBigInteger('funcionario_id')->nullable()->after('codigo_personal');
            $table->foreign('funcionario_id')->references('id')->on('funcionarios')->nullOnDelete();
            $table->index(['equipo_id', 'vigente'], 'idx_asig_equipo_vigente');
        });

        // ── Proyección de custodia actual ────────────────────────────────────
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->unsignedBigInteger('funcionario_id')->nullable()->after('id');
            $table->foreign('funcionario_id')->references('id')->on('funcionarios')->nullOnDelete();
            $table->unsignedBigInteger('acta_id')->nullable()->after('funcionario_id');
            $table->foreign('acta_id')->references('id')->on('actas')->nullOnDelete();
            $table->timestamp('desde')->nullable()->after('acta_id');
            $table->timestamp('hasta')->nullable()->after('desde');
            $table->string('modalidad', 20)->default('asignado')->after('hasta'); // asignado|custodia_fisica
        });
    }

    public function down(): void
    {
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->dropForeign(['funcionario_id']);
            $table->dropForeign(['acta_id']);
            $table->dropColumn(['funcionario_id', 'acta_id', 'desde', 'hasta', 'modalidad']);
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropForeign(['funcionario_id']);
            $table->dropIndex('idx_asig_equipo_vigente');
            $table->dropColumn([
                'estado_anterior', 'estado_resultante', 'vigente', 'fecha_fin',
                'origen', 'modalidad', 'codigo_personal', 'funcionario_id',
            ]);
        });
    }
};
