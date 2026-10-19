<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lote 15: el módulo de préstamos emite actas de novedad (tipo "prestamo").
 *
 * Cada préstamo queda vinculado a su acta de novedad para que el documento
 * (PDF/DOCX/XLSX/ZIP) y el historial del activo vengan del mismo origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->unsignedBigInteger('acta_id')->nullable()->after('user_id');

            $table->foreign('acta_id')
                ->references('id')
                ->on('actas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['acta_id']);
            $table->dropColumn('acta_id');
        });
    }
};
