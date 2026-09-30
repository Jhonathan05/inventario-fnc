<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->string('distrito', 150)
                ->nullable()
                ->after('piso')
                ->comment('Distrito al que pertenece el usuario');

            $table->string('seccional', 150)
                ->nullable()
                ->after('distrito')
                ->comment('Seccional a la que pertenece el usuario');
        });
    }

    public function down(): void
    {
        Schema::table('usuario_asignados', function (Blueprint $table) {
            $table->dropColumn(['distrito', 'seccional']);
        });
    }
};
