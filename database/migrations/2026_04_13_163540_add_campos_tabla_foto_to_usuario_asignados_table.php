<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->string('empresa_propietaria')->nullable()->after('cedula');
            $table->string('dependencia')->nullable()->after('empresa_propietaria');
            $table->string('fuente_recurso')->nullable()->after('dependencia');
            $table->string('empresa_funcionario')->nullable()->after('fuente_recurso');
            $table->string('tipo_vinculacion')->nullable()->after('empresa_funcionario');
            $table->string('shortname')->nullable()->after('tipo_vinculacion');
        });
    }

    public function down(): void
    {
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->dropColumn([
                'empresa_propietaria',
                'dependencia',
                'fuente_recurso',
                'empresa_funcionario',
                'tipo_vinculacion',
                'shortname',
            ]);
        });
    }
};
