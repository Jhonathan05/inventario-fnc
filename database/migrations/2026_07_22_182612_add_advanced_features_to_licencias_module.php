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
        // 1. Añadir campos de compra a la tabla licencias
        Schema::table('licencias', function (Blueprint $table) {
            $table->date('fecha_compra')->nullable()->after('estado');
            $table->string('correo_compra')->nullable()->after('fecha_compra');
            $table->date('fecha_renovacion')->nullable()->after('fecha_vencimiento');
            
            // Renombrar correo_asociado a correo_compra si existe (o simplemente dejar que correo_compra sea el nuevo)
            // Dado que correo_asociado acaba de ser creado en la unificación y la usuaria pidió correos separados, 
            // dejaremos correo_asociado intacto si ya lo usaba para algo, pero correo_compra será el oficial.
        });

        // 2. Crear tabla licencia_seriales
        Schema::create('licencia_seriales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licencia_id')->constrained('licencias')->onDelete('cascade');
            $table->string('serial');
            $table->enum('estado', ['Disponible', 'Reservado', 'Asignado', 'Inactivo'])->default('Disponible');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // 3. Añadir serial_id y correo_activacion a licencia_asignaciones
        Schema::table('licencia_asignaciones', function (Blueprint $table) {
            $table->foreignId('licencia_serial_id')->nullable()->after('licencia_id')->constrained('licencia_seriales')->onDelete('set null');
            $table->string('correo_activacion')->nullable()->after('funcionario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licencia_asignaciones', function (Blueprint $table) {
            $table->dropForeign(['licencia_serial_id']);
            $table->dropColumn(['licencia_serial_id', 'correo_activacion']);
        });

        Schema::dropIfExists('licencia_seriales');

        Schema::table('licencias', function (Blueprint $table) {
            $table->dropColumn(['fecha_compra', 'correo_compra', 'fecha_renovacion']);
        });
    }
};
