<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete();

            // Snapshot del usuario en el momento de la acción
            $table->string('usuario_nombre', 150)->nullable();
            $table->string('usuario_cedula', 20)->nullable();
            $table->string('usuario_empresa_propietaria', 150)->nullable();
            $table->string('usuario_dependencia', 150)->nullable();
            $table->string('usuario_fuente_recurso', 150)->nullable();
            $table->string('usuario_empresa_funcionario', 150)->nullable();
            $table->string('usuario_tipo_vinculacion', 100)->nullable();
            $table->string('usuario_shortname', 100)->nullable();
            $table->string('usuario_departamento', 100)->nullable();
            $table->string('usuario_ciudad', 100)->nullable();
            $table->string('usuario_cargo', 100)->nullable();
            $table->string('usuario_area', 100)->nullable();
            $table->string('usuario_piso', 20)->nullable();
            $table->string('usuario_distrito', 150)->nullable();
            $table->string('usuario_seccional', 150)->nullable();

            // Información de la acción
            $table->enum('tipo_accion', [
                'asignacion',
                'reemplazo',
                'retiro',
                'mantenimiento',
                'baja',
                'restauracion',
            ])->default('asignacion');

            $table->string('motivo', 500)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('entregado_por', 150)->nullable();

            // Quién registró la acción en el sistema
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_accion');

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas frecuentes
            $table->index('equipo_id', 'idx_asig_equipo');
            $table->index('tipo_accion', 'idx_asig_tipo_accion');
            $table->index('fecha_accion', 'idx_asig_fecha');
            $table->index('usuario_cedula', 'idx_asig_cedula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
