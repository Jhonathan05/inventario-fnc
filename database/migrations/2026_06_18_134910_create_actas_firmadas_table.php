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
        Schema::create('actas_firmadas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_acta')->unique();
            $table->string('tipo_acta');
            $table->date('fecha_documento');
            $table->text('observaciones')->nullable();
            $table->string('archivo_pdf');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actas_firmadas');
    }
};
