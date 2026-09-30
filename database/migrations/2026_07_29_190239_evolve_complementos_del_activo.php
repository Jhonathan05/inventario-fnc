<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir obligatorio a la pivot
        Schema::table('tipo_recurso_complemento', function (Blueprint $table) {
            $table->boolean('obligatorio')->default(false)->after('orden');
        });

        // 2. Cambiar equipo_id a nullable y nullOnDelete en activo_complementos
        // En MySQL, para cambiar la llave foránea, es mejor dropearla y recrearla
        Schema::table('activo_complementos', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->foreignId('equipo_id')->nullable()->change();
            $table->foreign('equipo_id')->references('id')->on('equipos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activo_complementos', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->foreignId('equipo_id')->nullable(false)->change();
            $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
        });

        Schema::table('tipo_recurso_complemento', function (Blueprint $table) {
            $table->dropColumn('obligatorio');
        });
    }
};
