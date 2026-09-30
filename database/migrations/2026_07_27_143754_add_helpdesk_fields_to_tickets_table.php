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
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('diagnostico_inicial')->nullable()->after('descripcion');
            $table->string('causa_probable')->nullable()->after('diagnostico_inicial');
            $table->text('observaciones_tecnicas')->nullable()->after('causa_probable');
            $table->dateTime('fecha_diagnostico')->nullable()->after('observaciones_tecnicas');
            $table->foreignId('diagnostico_user_id')->nullable()->after('fecha_diagnostico')->constrained('users')->nullOnDelete();
            
            $table->text('solucion_aplicada')->nullable()->after('diagnostico_user_id');
            $table->dateTime('fecha_solucion')->nullable()->after('solucion_aplicada');
            $table->dateTime('fecha_cierre')->nullable()->after('fecha_solucion');
            $table->string('tiempo_invertido')->nullable()->after('fecha_cierre');
            $table->text('observaciones_finales')->nullable()->after('tiempo_invertido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['diagnostico_user_id']);
            $table->dropColumn([
                'diagnostico_inicial',
                'causa_probable',
                'observaciones_tecnicas',
                'fecha_diagnostico',
                'diagnostico_user_id',
                'solucion_aplicada',
                'fecha_solucion',
                'fecha_cierre',
                'tiempo_invertido',
                'observaciones_finales'
            ]);
        });
    }
};
