<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_recurso_id')->constrained('tipo_recursos')->restrictOnDelete();
            $table->string('serial')->unique();
            $table->string('placa')->nullable();
            $table->string('marca');
            $table->string('modelo');
            $table->string('nombre_equipo');
            $table->enum('estado_operativo', ['activo', 'mantenimiento', 'baja'])->default('activo');
            $table->text('razon_estado')->nullable();
            $table->string('procesador')->nullable();
            $table->string('ram')->nullable();
            $table->string('disco')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->date('fecha_compra')->nullable();
            $table->date('fin_garantia')->nullable();
            $table->string('tiempo_uso')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
