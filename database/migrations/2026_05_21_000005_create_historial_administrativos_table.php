<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_administrativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete();

            // Quién hizo el cambio
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tipo_cambio', 100)
                ->comment('Ej: edicion, cambio_serial, cambio_estado, eliminacion, restauracion');

            $table->string('campo_modificado', 100)->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->string('descripcion', 500)->nullable();

            $table->timestamps();
            // Sin softDeletes — la auditoría es inmutable

            // Índices
            $table->index('equipo_id', 'idx_ha_equipo');
            $table->index('tipo_cambio', 'idx_ha_tipo');
            $table->index('created_at', 'idx_ha_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_administrativos');
    }
};
