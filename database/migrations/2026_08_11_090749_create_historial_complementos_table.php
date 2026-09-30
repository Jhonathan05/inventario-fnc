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
        Schema::create('historial_complementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complemento_id')->constrained('activo_complementos')->cascadeOnDelete();
            $table->string('evento');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha');
            
            $table->foreignId('activo_origen_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->foreignId('activo_destino_id')->nullable()->constrained('equipos')->nullOnDelete();
            
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            
            $table->string('campo_modificado')->nullable();
            $table->string('valor_anterior')->nullable();
            $table->string('valor_nuevo')->nullable();
            
            $table->text('observacion')->nullable();
            $table->json('informacion_adicional')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_complementos');
    }
};
