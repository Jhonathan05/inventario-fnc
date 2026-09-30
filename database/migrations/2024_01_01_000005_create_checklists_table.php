<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('responsable_ti')->nullable();
            $table->string('orden_trabajo')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('cruce_av')->nullable();
            $table->string('crece_software')->nullable();
            $table->string('resultado')->nullable();
            $table->string('tipo_aprobado')->nullable();
            $table->string('fnc')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
