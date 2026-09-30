<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorizaciones_activos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('asignacion_id')->nullable()->constrained('asignaciones')->nullOnDelete();
            $table->string('cedula', 20)->index();
            $table->string('nombre_funcionario', 150)->nullable();
            $table->string('archivo');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cedula', 'created_at'], 'idx_autorizaciones_cedula_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizaciones_activos');
    }
};
