<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja', 'asignado', 'disponible', 'almacenado') NOT NULL DEFAULT 'activo'");

        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN tipo_accion ENUM('asignacion', 'reemplazo', 'retiro', 'mantenimiento', 'baja', 'restauracion', 'inventario_fisico') NOT NULL DEFAULT 'asignacion'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN tipo_accion ENUM('asignacion', 'reemplazo', 'retiro', 'mantenimiento', 'baja', 'restauracion') NOT NULL DEFAULT 'asignacion'");

        DB::statement("ALTER TABLE equipos MODIFY COLUMN estado_operativo ENUM('activo', 'mantenimiento', 'baja', 'asignado', 'disponible') NOT NULL DEFAULT 'activo'");
    }
};
