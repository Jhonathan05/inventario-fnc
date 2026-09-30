<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_pdf', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150)
                ->comment('Nombre descriptivo de la plantilla');

            $table->enum('tipo', ['acta_entrega', 'otro'])
                ->default('acta_entrega');

            $table->longText('contenido')
                ->comment('HTML con variables {{variable}} para reemplazo dinámico');

            $table->boolean('activa')
                ->default(true)
                ->comment('Solo una plantilla activa por tipo');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('tipo', 'idx_plantilla_tipo');
            $table->index('activa', 'idx_plantilla_activa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_pdf');
    }
};
