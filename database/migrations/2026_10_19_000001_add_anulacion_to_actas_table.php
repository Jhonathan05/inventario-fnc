<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lote 18 — Anulación de actas de novedad.
 *
 * El estado ("valida"/"anulada") junto con la trazabilidad (fecha, usuario y
 * motivo) vive en la propia acta: los ítems se conservan intactos como
 * historial, pero dejan de ser vinculantes (el servicio de anulación revierte
 * el estado operativo de los activos y cancela préstamos asociados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->string('estado_acta', 20)->default('valida')->after('observaciones')->index();
            $table->timestamp('anulada_en')->nullable()->after('estado_acta');
            $table->unsignedBigInteger('anulada_por')->nullable()->after('anulada_en');
            $table->text('anulada_motivo')->nullable()->after('anulada_por');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn(['estado_acta', 'anulada_en', 'anulada_por', 'anulada_motivo']);
        });
    }
};
