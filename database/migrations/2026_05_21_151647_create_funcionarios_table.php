<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->string('identificacion', 20)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100)->nullable();
            
            // Atributos de vinculación
            $table->string('cargo', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('empresa_funcionario', 150)->nullable();
            $table->string('tipo_vinculacion', 100)->nullable();
            $table->string('estado', 20)->default('Activo'); // Activo, Inactivo
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
