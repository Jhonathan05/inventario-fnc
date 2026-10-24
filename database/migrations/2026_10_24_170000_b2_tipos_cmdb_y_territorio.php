<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lote B2 — tipos de recurso oficiales del CMDB + territorio.
 *
 *  - `tipo_recursos.es_catalogo_cmdb`: marca los 9 tipos oficiales de las
 *    listas de validación (EQUIPO ESCRITORIO, EQUIPO PORTATIL, EQUIPO TODO EN
 *    UNO, TABLETA, ESCANER, MONITOR, IMPRESORA TERMICA, IMPRESORA
 *    MULTIFUNCIONAL, IMPRESORA MONOFUNCIONAL). El importador valida contra
 *    ellos; el resto de tipos del sistema (switches, servidores, teléfonos…)
 *    queda fuera del catálogo CMDB pero sigue siendo válido.
 *  - `territorios`: jerarquía Circunscripción → Seccional → Municipio
 *    (fuente: docs/Formatos/territorio.xlsx, Comité Tolima: 6 circunscripciones,
 *    8 seccionales, 34 municipios). Editable desde el módulo Catálogos.
 */
return new class extends Migration
{
    private const TIPOS_CMDB = [
        'EQUIPO ESCRITORIO', 'EQUIPO PORTATIL', 'EQUIPO TODO EN UNO', 'TABLETA',
        'ESCANER', 'MONITOR', 'IMPRESORA TERMICA', 'IMPRESORA MULTIFUNCIONAL',
        'IMPRESORA MONOFUNCIONAL',
    ];

    public function up(): void
    {
        Schema::table('tipo_recursos', function (Blueprint $table) {
            $table->boolean('es_catalogo_cmdb')->default(false)->after('es_computador');
            $table->boolean('activo')->default(true)->after('es_catalogo_cmdb'); // desactivar sin borrar (módulo Catálogos)
        });

        // Los 9 tipos oficiales (la colación es insensible a tildes:
        // "Equipo Portatil" ≡ "EQUIPO PORTATIL", se reutilizan los existentes)
        foreach (self::TIPOS_CMDB as $nombre) {
            DB::table('tipo_recursos')->updateOrInsert(
                ['nombre' => $nombre],
                ['es_catalogo_cmdb' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        Schema::create('territorios', function (Blueprint $table) {
            $table->id();
            $table->string('circunscripcion', 80);
            $table->string('seccional', 80);
            $table->string('municipio', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('municipio');
            $table->index('seccional');
            $table->index('circunscripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territorios');
        Schema::table('tipo_recursos', function (Blueprint $table) {
            $table->dropColumn('es_catalogo_cmdb');
        });
        DB::table('tipo_recursos')->whereIn('nombre', [
            'MONITOR', 'IMPRESORA TERMICA', 'IMPRESORA MULTIFUNCIONAL', 'IMPRESORA MONOFUNCIONAL',
        ])->delete();
    }
};
