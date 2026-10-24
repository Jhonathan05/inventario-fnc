<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correcciones de datos — pestaña "Correcciones" del módulo Importar.
 *
 * La importación del CMDB es NO condicionada: las filas importan aunque su
 * información esté incompleta, y cada faltante (valor vacío, placeholder
 * PENDIENTE/N/A/SIN REGISTRO, valor fuera de catálogo, custodio faltante)
 * queda registrado aquí como un pendiente corregible directamente desde la
 * vista, hasta completar el 100 % de la información real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correcciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->unsignedBigInteger('funcionario_id')->nullable(); // referencia blanda (sin FK: el maestro cambia)

            // Qué se corrige
            $table->string('seccion', 30)->default('ficha');   // identidad|ficha|custodia|catalogos
            $table->string('campo', 60);                        // serial, placa, estado_uso, custodia.cedula…
            $table->string('etiqueta', 140);                    // nombre humano con columna CMDB
            $table->text('valor_actual')->nullable();
            $table->text('mensaje');                            // descripción del faltante
            $table->string('severidad', 16)->default('media'); // alta|media|baja
            $table->string('tipo_dato', 20)->default('texto'); // texto|fecha|catalogo
            $table->string('catalogo', 60)->nullable();        // origen de opciones (diccionarios/catálogos)

            // Ciclo de vida
            $table->string('estado', 16)->default('pendiente'); // pendiente|resuelta|ignorada
            $table->text('valor_corregido')->nullable();
            $table->text('justificacion')->nullable();          // cuando se ignora con causa válida
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelta_en')->nullable();
            $table->string('origen', 20)->default('import');    // import|escaneo|manual

            $table->timestamps();

            $table->index(['estado', 'severidad']);
            $table->index(['equipo_id', 'campo']);
            $table->index('seccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correcciones');
    }
};
