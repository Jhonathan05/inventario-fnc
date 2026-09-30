<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diccionarios', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 50)->index(); // 'cargo', 'empresa_funcionario', 'tipo_personal', etc.
            $table->string('valor');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diccionarios');
    }
};
