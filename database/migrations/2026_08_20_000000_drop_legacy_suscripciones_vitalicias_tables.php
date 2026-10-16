<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina las tablas del módulo legacy de Suscripciones/Vitalicias,
     * reemplazado por el módulo unificado de Licencias.
     */
    public function up(): void
    {
        // Primero las tablas hijas (con claves foráneas), luego las padre.
        Schema::dropIfExists('suscripcion_asignaciones');
        Schema::dropIfExists('suscripcion_historiales');
        Schema::dropIfExists('suscripciones');

        Schema::dropIfExists('vitalicia_asignaciones');
        Schema::dropIfExists('vitalicia_historiales');
        Schema::dropIfExists('vitalicias');
    }

    /**
     * No se recrea el esquema: el módulo fue eliminado del proyecto.
     */
    public function down(): void
    {
        //
    }
};
