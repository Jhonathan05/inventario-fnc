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
            $table->unsignedBigInteger('responsable_id')->nullable()->after('user_id');
            $table->string('responsable_nombre')->nullable()->after('responsable_id');
            $table->string('responsable_cedula')->nullable()->after('responsable_nombre');
            $table->string('tipo_usuario')->nullable()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_responsabilidad', function (Blueprint $table) {
            //
        });
    }
};
