<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acta de novedad de activos (fase 2, flujo 1): cabecera del movimiento.
 *
 * El acta agrupa N activos sin relación entre sí y define, por tipo de
 * movimiento, el estado resultante de todos ellos. Reproduce el formato
 * institucional FE-BS-F-0069 (Planilla de novedad): tipo de novedad,
 * ubicación, fecha, la tabla de ítems y el doble bloque de firmas
 * (quien entrega / quien recibe).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas', function (Blueprint $table) {
            $table->id();

            // Movimiento
            $table->string('tipo_accion', 40);
            $table->dateTime('fecha_accion');
            $table->string('ubicacion', 100)->nullable();
            $table->string('destino', 30)->nullable(); // retiro_definitivo: disponible|baja
            $table->date('fecha_inicio')->nullable(); // préstamo
            $table->date('fecha_fin_probable')->nullable(); // préstamo
            $table->text('observaciones')->nullable();

            // Bloque de firmas del formato: quien entrega
            $table->string('entregador_dependencia', 150)->nullable();
            $table->string('entregador_nombre', 150)->nullable();
            $table->string('entregador_codigo', 30)->nullable();
            $table->string('entregador_cargo', 150)->nullable();

            // Bloque de firmas del formato: quien recibe
            $table->string('recibe_dependencia', 150)->nullable();
            $table->string('recibe_nombre', 150)->nullable();
            $table->string('recibe_codigo', 30)->nullable();
            $table->string('recibe_cargo', 150)->nullable();

            // Snapshot del responsable del acta (bloque de custodia del CMDB)
            $table->string('usuario_empresa_propietaria', 100)->nullable();
            $table->string('usuario_dependencia', 150)->nullable();
            $table->string('usuario_fuente_recurso', 50)->nullable();
            $table->string('usuario_empresa_funcionario', 100)->nullable();
            $table->string('usuario_tipo_vinculacion', 50)->nullable();
            $table->string('usuario_cedula', 30)->nullable();
            $table->string('usuario_shortname', 100)->nullable();
            $table->string('usuario_nombre', 150)->nullable();
            $table->string('usuario_departamento', 100)->nullable();
            $table->string('usuario_ciudad', 100)->nullable();
            $table->string('usuario_cargo', 150)->nullable();
            $table->string('usuario_area', 150)->nullable();
            $table->string('usuario_piso', 50)->nullable();
            $table->string('usuario_distrito', 150)->nullable();
            $table->string('usuario_seccional', 150)->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('acta_previa_id')->nullable()->constrained('actas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo_accion');
            $table->index('fecha_accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas');
    }
};
