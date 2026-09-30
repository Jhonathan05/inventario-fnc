<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorizaciones_activos', function (Blueprint $table) {
            $table->string('estado', 20)->default('cargada')->after('tamano_bytes');
            $table->timestamp('consumida_en')->nullable()->after('estado');
            $table->foreignId('consumida_por_user_id')->nullable()->after('consumida_en')->constrained('users')->nullOnDelete();
            $table->timestamp('anulada_en')->nullable()->after('consumida_por_user_id');
            $table->foreignId('anulada_por_user_id')->nullable()->after('anulada_en')->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 500)->nullable()->after('anulada_por_user_id');

            $table->index(['cedula', 'estado'], 'idx_autorizaciones_cedula_estado');
        });
    }

    public function down(): void
    {
        Schema::table('autorizaciones_activos', function (Blueprint $table) {
            $table->dropIndex('idx_autorizaciones_cedula_estado');
            $table->dropConstrainedForeignId('consumida_por_user_id');
            $table->dropConstrainedForeignId('anulada_por_user_id');
            $table->dropColumn(['estado', 'consumida_en', 'anulada_en', 'motivo_anulacion']);
        });
    }
};
