<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('activo_fijo', 100)
                ->nullable()
                ->after('serial')
                ->comment('Identificador administrativo interno del equipo');

            $table->index('activo_fijo', 'idx_equipos_activo_fijo');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex('idx_equipos_activo_fijo');
            $table->dropColumn('activo_fijo');
        });
    }
};
