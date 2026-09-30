<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained()->cascadeOnDelete();
            
            // Persona a la que se le presta (puede ser texto libre o un ID si es necesario, pero usaremos texto libre para flexibilidad igual que Asignacion Responsabilidad, o referenciar al usuario si aplica)
            $table->string('persona_nombre');
            $table->string('persona_documento')->nullable();
            
            // Usuario del sistema que registró el préstamo
            $table->foreignId('user_id')->constrained('users');
            
            // Fechas clave
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_devolucion_prevista');
            $table->dateTime('fecha_devolucion_real')->nullable();
            
            // Duración original registrada al momento del préstamo (ej. '3 días', '1 semana')
            $table->string('duracion')->nullable();
            
            // Estado del préstamo
            $table->enum('estado', ['Pendiente', 'Activo', 'Vencido', 'Devuelto', 'Cancelado'])->default('Activo');
            
            // Detalles
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            
            // Detalles de devolución
            $table->foreignId('usuario_devolucion_id')->nullable()->constrained('users');
            $table->string('estado_fisico_devolucion')->nullable();
            $table->text('observaciones_devolucion')->nullable();
            $table->text('complementos_devueltos')->nullable(); // Para almacenar JSON
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
