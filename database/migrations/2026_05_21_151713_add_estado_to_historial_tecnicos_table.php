<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_tecnicos', function (Blueprint $table) {
            $table->enum('estado', ['Creado', 'En proceso', 'Suspendido', 'Finalizado'])->default('Creado')->after('tipo_evento');
        });
    }

    public function down(): void
    {
        Schema::table('historial_tecnicos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
