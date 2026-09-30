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
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();
            
            // Relación polimórfica (seguible_id, seguible_type)
            $table->morphs('seguible');
            
            // Usuario que realiza el avance (puede ser null si es completamente automatizado por sistema y no atribuido)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('tipo_avance')->nullable();
            $table->text('comentario')->nullable();
            $table->boolean('is_system')->default(false); // Para separar mensajes automáticos de sistema
            
            $table->json('archivos')->nullable();
            $table->json('metadata')->nullable(); // Para almacenar datos adicionales como estado anterior y nuevo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};
