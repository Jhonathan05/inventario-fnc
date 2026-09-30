<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_asignados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->unique()->constrained('equipos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cedula');
            $table->string('departamento')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('cargo')->nullable();
            $table->string('area')->nullable();
            $table->string('piso')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_asignados');
    }
};
