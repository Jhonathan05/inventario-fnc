<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2: regla de negocio "un computador por funcionario".
 *
 * - tipo_recursos gana el flag `es_computador` (Equipo Escritorio, Portátil
 *   y Todo en Uno lo llevan en true).
 * - actas gana `autorizacion_path` (PDF de autorización del jefe superior
 *   cuando se permite un segundo computador permanente) y
 *   `autorizacion_comentario`.
 */
return new class extends Migration
{
    private array $computadores = [
        'EQUIPO ESCRITORIO',
        'EQUIPO PORTATIL',
        'EQUIPO PORTÁTIL',
        'EQUIPO TODO EN UNO',
    ];

    public function up(): void
    {
        Schema::table('tipo_recursos', function (Blueprint $table) {
            $table->boolean('es_computador')->default(false)->after('nombre');
        });

        // Marcar los tipos de computador
        foreach ($this->computadores as $nombre) {
            DB::table('tipo_recursos')
                ->whereRaw('UPPER(TRIM(nombre)) = ?', [$nombre])
                ->update(['es_computador' => true]);
        }

        Schema::table('actas', function (Blueprint $table) {
            $table->string('autorizacion_path')->nullable()->after('observaciones');
            $table->string('autorizacion_comentario')->nullable()->after('autorizacion_path');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn(['autorizacion_path', 'autorizacion_comentario']);
        });
        Schema::table('tipo_recursos', function (Blueprint $table) {
            $table->dropColumn('es_computador');
        });
    }
};
