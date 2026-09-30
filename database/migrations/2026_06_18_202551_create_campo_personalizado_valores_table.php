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
        Schema::create('campo_personalizado_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_personalizado_id')->constrained('campos_personalizados')->onDelete('cascade');
            $table->unsignedBigInteger('entidad_id')->index(); // ID del equipo, licencia, etc.
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campo_personalizado_valores');
    }
};
