<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activo_complementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('catalogo_complemento_id')->constrained('catalogo_complementos')->restrictOnDelete();
            
            $table->string('estado', 50)->nullable(); // Bueno, Regular, Malo, Dañado, Nuevo
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serial', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->integer('cantidad')->default(1);
            $table->date('fecha_registro')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('equipo_id', 'idx_activo_comp_equipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activo_complementos');
    }
};
