<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete();

            $table->enum('tipo_evento', [
                'formateo',
                'cambio_disco',
                'cambio_ram',
                'mantenimiento_preventivo',
                'mantenimiento_correctivo',
                'instalacion_software',
                'limpieza',
                'reparacion',
                'observacion',
                'otro',
            ]);

            $table->string('descripcion', 500);
            $table->string('motivo', 500)->nullable();
            $table->date('fecha_evento');

            // Quién realizó el trabajo técnico
            $table->string('usuario_responsable', 150)
                ->comment('Nombre del técnico responsable');

            // Snapshot del usuario asignado en ese momento histórico
            $table->json('usuario_asignado_snapshot')
                ->nullable()
                ->comment('Datos del usuario asignado al equipo en el momento del evento');

            // Archivos adjuntos opcionales (rutas almacenadas como JSON)
            $table->json('archivos')->nullable();

            $table->text('observaciones')->nullable();

            // Quién registró en el sistema
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('equipo_id', 'idx_ht_equipo');
            $table->index('tipo_evento', 'idx_ht_tipo');
            $table->index('fecha_evento', 'idx_ht_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_tecnicos');
    }
};
