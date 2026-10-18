<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos oficiales del CMDB (hoja "Listas De Validación Equipos"):
 * estados operativos y razones del estado, con su descripción institucional.
 */
return new class extends Migration
{
    private array $estados = [
        ['codigo' => 'almacenado', 'nombre' => 'Almacenado', 'descripcion' => 'Todo activo que se encuentre reservado en una bodega.'],
        ['codigo' => 'disponible', 'nombre' => 'Disponible', 'descripcion' => 'Todo activo que se encuentre disponible nuevo o usado para asignación a usuario final.'],
        ['codigo' => 'transito', 'nombre' => 'Tránsito', 'descripcion' => 'Todo activo que se encuentre en circulación.'],
        ['codigo' => 'en_operacion', 'nombre' => 'En operación', 'descripcion' => 'Todo activo que se encuentre en uso por parte de un colaborador.'],
        ['codigo' => 'asignacion_responsable', 'nombre' => 'Asignación responsable', 'descripcion' => 'Activo utilizado por usuario cuyo cargo corresponda a aprendices o terceros; será asignado a un usuario responsable.'],
        ['codigo' => 'prestamo', 'nombre' => 'Préstamo', 'descripcion' => 'Todo activo asignado en calidad de préstamo por un tiempo no mayor a 15 días calendario.'],
        ['codigo' => 'reparacion', 'nombre' => 'Reparación', 'descripcion' => 'Activos en proceso de alistamiento, soporte o garantía (antes "mantenimiento").'],
        ['codigo' => 'baja', 'nombre' => 'Baja', 'descripcion' => 'Todo activo que se encuentre en proceso para disposición final.'],
        ['codigo' => 'procesar_baja', 'nombre' => 'Procesar baja', 'descripcion' => 'Todo activo que se encuentre en proceso de baja.'],
        ['codigo' => 'hurto', 'nombre' => 'Hurto', 'descripcion' => 'Todo activo hurtado. Debe contar con su soporte correspondiente (denuncia).'],
        ['codigo' => 'pendiente_estado', 'nombre' => 'Pendiente estado', 'descripcion' => 'Activo con estado pendiente por definir.'],
    ];

    private array $razones = [
        ['codigo' => 'custodia', 'nombre' => 'Custodia', 'descripcion' => 'Todo activo que ingrese a bodega estará almacenado 15 días hábiles; después se cambia a disponible para su asignación.'],
        ['codigo' => 'alistamiento_soporte', 'nombre' => 'Alistamiento / Soporte', 'descripcion' => 'Activos que se encuentren en proceso de alistamiento, mantenimiento, soporte o garantía.'],
        ['codigo' => 'renovacion', 'nombre' => 'Renovación', 'descripcion' => 'Activo nuevo que ingresa a bodega con la finalidad de realizar la actualización de los mismos.'],
        ['codigo' => 'despacho', 'nombre' => 'Despacho', 'descripcion' => 'Activo que se encuentra en movimiento o traslado desde la oficina Bogotá para las sedes, comités e inspecciones a nivel nacional.'],
        ['codigo' => 'retorno', 'nombre' => 'Retorno', 'descripcion' => 'Activo que se encuentra en movimiento o traslado desde sedes, comités o inspecciones a nivel nacional hacia la oficina Bogotá.'],
        ['codigo' => 'temporal', 'nombre' => 'Temporal / Préstamo', 'descripcion' => 'Equipos "comodín" de contingencia, asignados de forma provisional por soporte técnico o para eventos y consultores externos.'],
        ['codigo' => 'obsoleto', 'nombre' => 'Obsoleto', 'descripcion' => 'Activo obsoleto.'],
        ['codigo' => 'asignacion_responsable', 'nombre' => 'Asignación responsable', 'descripcion' => 'Asignación a usuario responsable para aprendices o terceros.'],
        ['codigo' => 'hurto', 'nombre' => 'Hurto', 'descripcion' => 'Activo hurtado o extraviado, con su soporte correspondiente (denuncia).'],
        ['codigo' => 'devuelto_proveedor', 'nombre' => 'Devuelto a proveedor', 'descripcion' => 'Activo en proceso de devolución al proveedor por conceptos de renta y/o leasing.'],
        ['codigo' => 'donacion', 'nombre' => 'Donación', 'descripcion' => 'Equipo dado en donación por parte de FNC o Almacafé.'],
    ];

    public function up(): void
    {
        Schema::create('catalogo_estados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 60);
            $table->string('descripcion', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('catalogo_razones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 60);
            $table->string('descripcion', 500)->nullable();
            $table->timestamps();
        });

        foreach ($this->estados as $estado) {
            DB::table('catalogo_estados')->insert($estado + ['created_at' => now(), 'updated_at' => now()]);
        }
        foreach ($this->razones as $razon) {
            DB::table('catalogo_razones')->insert($razon + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_estados');
        Schema::dropIfExists('catalogo_razones');
    }
};
