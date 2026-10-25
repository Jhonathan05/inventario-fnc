<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fila real del archivo CMDB que originó cada activo.
 *
 * El `consecutivo` es un identificador interno del sistema y no sirve para
 * ubicar el registro en el Excel (se desalinea al descartar duplicados). Para
 * corregir un pendiente sin ambigüedad, el modal muestra la fila del CMDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedInteger('fila_cmdb')->nullable()->after('consecutivo');
            $table->index('fila_cmdb');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['fila_cmdb']);
            $table->dropColumn('fila_cmdb');
        });
    }
};
