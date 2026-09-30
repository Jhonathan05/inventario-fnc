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
        Schema::create('vitalicia_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitalicia_id')->constrained('vitalicias')->onDelete('cascade');
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->onDelete('set null');
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->onDelete('set null');
            $table->date('fecha_asignacion');
            $table->text('observaciones')->nullable();
            $table->foreignId('responsable_ti_id')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitalicia_asignaciones');
    }
};
