<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE historial_tecnicos MODIFY COLUMN tipo_evento ENUM('formateo','cambio_disco','cambio_ram','mantenimiento_preventivo','mantenimiento_correctivo','instalacion_software','limpieza','reparacion','observacion','otro','requerimiento','incidente') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE historial_tecnicos MODIFY COLUMN tipo_evento ENUM('formateo','cambio_disco','cambio_ram','mantenimiento_preventivo','mantenimiento_correctivo','instalacion_software','limpieza','reparacion','observacion','otro') NOT NULL");
    }
};
