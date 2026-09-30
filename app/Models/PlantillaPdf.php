<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaPdf extends Model
{
    use HasFactory;

    protected $table = 'plantillas_pdf';

    protected $fillable = [
        'nombre',
        'tipo',
        'contenido',
        'activa',
        'user_id',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    const TIPOS = [
        'acta_entrega' => 'Acta de Entrega',
        'otro'         => 'Otro',
    ];

    /**
     * Variables disponibles para usar en las plantillas.
     */
    const VARIABLES_DISPONIBLES = [
        // Equipo
        '{{nombre_equipo}}'   => 'Nombre del equipo',
        '{{serial}}'          => 'Serial del equipo',
        '{{activo_fijo}}'     => 'Activo fijo / Identificador',
        '{{placa}}'           => 'Placa del equipo',
        '{{marca}}'           => 'Marca',
        '{{modelo}}'          => 'Modelo',
        '{{tipo_recurso}}'    => 'Tipo de recurso',
        '{{procesador}}'      => 'Procesador',
        '{{ram}}'             => 'Memoria RAM',
        '{{disco}}'           => 'Disco duro',
        '{{sistema_operativo}}' => 'Sistema operativo',
        '{{estado_operativo}}' => 'Estado operativo',
        '{{fecha_compra}}'    => 'Fecha de compra',
        '{{fin_garantia}}'    => 'Fin de garantía',
        // Usuario asignado
        '{{nombre_usuario}}'      => 'Nombre del usuario',
        '{{cedula}}'              => 'Cédula del usuario',
        '{{cargo}}'               => 'Cargo',
        '{{area}}'                => 'Área',
        '{{dependencia}}'         => 'Dependencia',
        '{{empresa_propietaria}}' => 'Empresa propietaria',
        '{{empresa_funcionario}}' => 'Empresa funcionario',
        '{{departamento}}'        => 'Departamento',
        '{{ciudad}}'              => 'Ciudad',
        '{{piso}}'                => 'Piso / Ubicación',
        '{{distrito}}'            => 'Distrito',
        '{{seccional}}'           => 'Seccional',
        '{{shortname}}'           => 'Shortname / Usuario de red',
        // Asignación
        '{{fecha_asignacion}}'    => 'Fecha de asignación',
        '{{entregado_por}}'       => 'Entregado por',
        '{{tipo_accion}}'         => 'Tipo de acción',
        '{{motivo}}'              => 'Motivo',
        // Sistema
        '{{fecha_generacion}}'    => 'Fecha de generación del documento',
        '{{usuario_sistema}}'     => 'Usuario del sistema que genera el PDF',
        '{{logo_fnc}}'            => 'Logo de la FNC (Imagen)',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Métodos ───────────────────────────────────────────────────────────────

    /**
     * Procesa las variables dinámicas en el contenido de la plantilla.
     */
    public function procesarVariables(array $datos): string
    {
        $contenido = $this->contenido;

        foreach ($datos as $variable => $valor) {
            $contenido = str_replace('{{' . $variable . '}}', $valor ?? '—', $contenido);
        }

        return $contenido;
    }
}
