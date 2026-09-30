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
        Schema::create('plantillas_exportacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('modulo', 50)->default('equipos')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('configuracion_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_exportacion');
    }
};
