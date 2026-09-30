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
        Schema::table('licencias', function (Blueprint $table) {
            $table->boolean('requiere_correo')->default(false)->after('observaciones');
            $table->string('correo_asociado')->nullable()->after('requiere_correo');
            $table->string('usuario_asignado')->nullable()->after('correo_asociado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropColumn(['requiere_correo', 'correo_asociado', 'usuario_asignado']);
        });
    }
};
