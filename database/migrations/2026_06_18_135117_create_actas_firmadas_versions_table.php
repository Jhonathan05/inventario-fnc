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
        Schema::create('actas_firmadas_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_firmada_id')->constrained('actas_firmadas')->cascadeOnDelete();
            $table->string('archivo_pdf');
            $table->integer('version');
            $table->text('motivo_cambio');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actas_firmadas_versions');
    }
};
