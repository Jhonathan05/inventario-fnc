<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lote B4 — maestro de funcionarios del CMDB (hoja "Listado funcionarios").
 *
 * Columnas nuevas para los 4.241 funcionarios del maestro (correo, jefe,
 * género, ceco SAP, fecha de vencimiento, proceso, tipo de recurso, división)
 * + índice único sobre identificacion_hash (la columna identificacion está
 * cifrada con IV aleatorio, así que el hash es la clave real de deduplicación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->string('div', 30)->nullable()->after('identificacion_hash');
            $table->string('email', 150)->nullable()->after('div');
            $table->string('jefe_cedula', 20)->nullable()->after('email');
            $table->string('jefe_nombre', 150)->nullable()->after('jefe_cedula');
            $table->string('jefe_email', 150)->nullable()->after('jefe_nombre');
            $table->string('genero', 20)->nullable()->after('jefe_email');
            $table->string('ceco', 40)->nullable()->after('genero');
            $table->date('fecha_vencimiento')->nullable()->after('ceco');
            $table->string('proceso', 120)->nullable()->after('fecha_vencimiento');
            $table->string('tipo_recurso', 120)->nullable()->after('proceso');
        });

        // Deduplicación real por cédula: el hash es determinista, la columna
        // cifrada no (IV aleatorio por fila).
        $existe = collect(DB::select("SHOW INDEX FROM funcionarios"))->pluck('Key_name')->contains('funcionarios_identificacion_hash_unique');
        if (! $existe) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->unique('identificacion_hash');
            });
        }
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn([
                'div', 'email', 'jefe_cedula', 'jefe_nombre', 'jefe_email',
                'genero', 'ceco', 'fecha_vencimiento', 'proceso', 'tipo_recurso',
            ]);
        });
    }
};
