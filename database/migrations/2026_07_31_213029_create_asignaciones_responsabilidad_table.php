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
        Schema::create('asignaciones_responsabilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('nombre_usuario', 150);
            $table->string('documento', 50)->nullable();
            $table->string('empresa', 150)->nullable();
            $table->string('cargo', 150)->nullable();
            $table->string('proyecto', 150)->nullable();
            $table->string('area', 150)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('telefono', 50)->nullable();
            
            $table->date('fecha_inicio');
            $table->date('fecha_final_estimada')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->enum('estado', ['activa', 'finalizada'])->default('activa');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_responsabilidad');
    }
};
