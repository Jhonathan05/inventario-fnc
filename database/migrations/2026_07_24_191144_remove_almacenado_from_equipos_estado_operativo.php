<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar los equipos que tengan almacenado a disponible por si acaso
        DB::table('equipos')->where('estado_operativo', 'almacenado')->update(['estado_operativo' => 'disponible']);
        
        // Modificar el enum
        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja', 'asignado', 'disponible') NOT NULL DEFAULT 'activo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja', 'asignado', 'disponible', 'almacenado') NOT NULL DEFAULT 'activo'");
    }
};
