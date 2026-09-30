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
        Schema::table('campos_personalizados', function (Blueprint $table) {
            $table->boolean('mostrar_en_grilla')->default(false)->after('activo');
            $table->string('posicion_grilla_despues_de')->nullable()->after('mostrar_en_grilla');
            $table->string('exportar_excel_despues_de')->nullable()->after('posicion_grilla_despues_de');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_personalizados', function (Blueprint $table) {
            $table->dropColumn(['mostrar_en_grilla', 'posicion_grilla_despues_de', 'exportar_excel_despues_de']);
        });
    }
};
