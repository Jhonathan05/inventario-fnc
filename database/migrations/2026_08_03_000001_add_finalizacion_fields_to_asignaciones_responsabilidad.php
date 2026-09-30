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
        Schema::table('asignaciones_responsabilidad', function (Blueprint $table) {
            $table->date('fecha_final_real')->nullable();
            $table->string('motivo_finalizacion')->nullable();
            $table->text('observaciones_finales')->nullable();
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_responsabilidad', function (Blueprint $table) {
            $table->dropForeign(['finalizado_por']);
            $table->dropColumn([
                'fecha_final_real',
                'motivo_finalizacion',
                'observaciones_finales',
                'finalizado_por'
            ]);
        });
    }
};
