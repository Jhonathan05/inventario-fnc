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
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('fabricante')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('cantidad_comprada')->default(1);
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_vencimiento_global')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['Activa', 'Próxima a vencer', 'Vencida', 'Suspendida'])->default('Activa');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
