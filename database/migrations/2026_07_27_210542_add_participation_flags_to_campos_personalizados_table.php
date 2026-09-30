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
            $table->boolean('participa_exportacion_cmdb')->default(true)->after('mostrar_en_grilla');
            $table->boolean('participa_exportacion_completa')->default(true)->after('participa_exportacion_cmdb');
            $table->boolean('participa_reportes')->default(true)->after('participa_exportacion_completa');
            $table->boolean('participa_filtros')->default(false)->after('participa_reportes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_personalizados', function (Blueprint $table) {
            $table->dropColumn([
                'participa_exportacion_cmdb',
                'participa_exportacion_completa',
                'participa_reportes',
                'participa_filtros',
            ]);
        });
    }
};
