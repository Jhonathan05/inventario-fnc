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
        Schema::create('campos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 50)->default('equipos')->index(); // 'equipos', 'licencias', 'actas', etc.
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            // texto, textarea, numero, fecha, correo, telefono, boolean, select, multiselect, url, archivo
            $table->string('tipo', 50);
            $table->boolean('obligatorio')->default(false);
            $table->boolean('editable')->default(true);
            $table->boolean('visible')->default(true);
            $table->boolean('importable')->default(true);
            $table->boolean('exportable')->default(true);
            $table->boolean('exportar_por_defecto')->default(false);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_personalizados');
    }
};
