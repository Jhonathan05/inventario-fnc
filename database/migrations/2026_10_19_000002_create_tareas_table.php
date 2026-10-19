<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de tareas del tablero: pendientes creadas manualmente. Las
     * derivadas de datos incompletos de la CMDB no se persisten: se calculan
     * al vuelo y desaparecen cuando se corrige el dato de origen.
     */
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo', 160);
            $table->text('descripcion')->nullable();
            $table->string('modulo', 24)->default('general'); // general|equipos|funcionarios
            $table->string('prioridad', 12)->default('Media'); // Alta|Media|Baja
            $table->string('estado', 12)->default('Pendiente'); // Pendiente|Hecha
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hecha_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hecha_en')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
