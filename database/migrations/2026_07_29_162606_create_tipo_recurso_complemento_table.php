<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_recurso_complemento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_recurso_id')->constrained('tipo_recursos')->cascadeOnDelete();
            $table->foreignId('catalogo_complemento_id')->constrained('catalogo_complementos')->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['tipo_recurso_id', 'catalogo_complemento_id'], 'tipo_recurso_cat_comp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_recurso_complemento');
    }
};
