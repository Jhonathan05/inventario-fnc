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
        Schema::create('vitalicia_historiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vitalicia_id')->constrained('vitalicias')->onDelete('cascade');
            $table->string('accion');
            $table->text('detalles')->nullable();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitalicia_historiales');
    }
};
