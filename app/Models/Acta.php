<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Acta de novedad de activos (formato FE-BS-F-0069): agrupa N activos sin
 * relación entre sí en un solo movimiento y define, por tipo, el estado
 * resultante de todos ellos.
 */
class Acta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actas';

    protected $fillable = [
        'tipo_accion',
        'fecha_accion',
        'ubicacion',
        'destino',
        'fecha_inicio',
        'fecha_fin_probable',
        'observaciones',
        'autorizacion_path',
        'autorizacion_comentario',
        'entregador_dependencia',
        'entregador_nombre',
        'entregador_codigo',
        'entregador_cargo',
        'recibe_dependencia',
        'recibe_nombre',
        'recibe_codigo',
        'recibe_cargo',
        'usuario_empresa_propietaria',
        'usuario_dependencia',
        'usuario_fuente_recurso',
        'usuario_empresa_funcionario',
        'usuario_tipo_vinculacion',
        'usuario_cedula',
        'usuario_shortname',
        'usuario_nombre',
        'usuario_departamento',
        'usuario_ciudad',
        'usuario_cargo',
        'usuario_area',
        'usuario_piso',
        'usuario_distrito',
        'usuario_seccional',
        'user_id',
        'acta_previa_id',
        'estado_acta',
        'anulada_en',
        'anulada_por',
        'anulada_motivo',
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
        'fecha_inicio' => 'date',
        'fecha_fin_probable' => 'date',
        'anulada_en' => 'datetime',
    ];

    // ── Catálogo de movimientos (v3) ──────────────────────────────────────────

    public const TIPO_INVENTARIO_FISICO = 'inventario_fisico';
    public const TIPO_TRASLADO = 'traslado';
    public const TIPO_CAMBIO_RESPONSABLE = 'cambio_responsable';
    public const TIPO_RETIRO_DEFINITIVO = 'retiro_definitivo';
    public const TIPO_PRESTAMO = 'prestamo';
    public const TIPO_REPARACION = 'reparacion';

    public const TIPOS_ACCION = [
        self::TIPO_INVENTARIO_FISICO => 'Inventario Físico',
        self::TIPO_TRASLADO => 'Traslado',
        self::TIPO_CAMBIO_RESPONSABLE => 'Cambio de responsable',
        self::TIPO_RETIRO_DEFINITIVO => 'Retiro definitivo',
        self::TIPO_PRESTAMO => 'Préstamo',
        self::TIPO_REPARACION => 'Reparación',
    ];

    // ── Estados por movimiento ────────────────────────────────────────────────

    /**
     * Estados operativos del CMDB con los que trabaja cada movimiento.
     * "deja" en null significa que el movimiento no cambia el estado
     * (traslado y cambio de responsable) o que es condicional (reparación:
     * entrada a reparación o salida hacia el último responsable).
     */
    public const REGLAS_MOVIMIENTO = [
        self::TIPO_INVENTARIO_FISICO => ['requiere' => ['disponible'], 'deja' => ['en_operacion', 'asignacion_responsable']],
        self::TIPO_TRASLADO => ['requiere' => ['en_operacion', 'asignacion_responsable'], 'deja' => null],
        self::TIPO_CAMBIO_RESPONSABLE => ['requiere' => ['en_operacion', 'asignacion_responsable'], 'deja' => null],
        self::TIPO_RETIRO_DEFINITIVO => ['requiere' => ['en_operacion', 'asignacion_responsable', 'reparacion'], 'deja' => ['disponible', 'baja']],
        self::TIPO_PRESTAMO => ['requiere' => ['disponible'], 'deja' => ['prestamo']],
        self::TIPO_REPARACION => ['requiere' => ['disponible', 'en_operacion', 'asignacion_responsable', 'reparacion'], 'deja' => null],
    ];

    // ── Estado del documento acta (Lote 18) ───────────────────────────────────
    public const ESTADO_VALIDA  = 'valida';
    public const ESTADO_ANULADA = 'anulada';

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario que anuló el acta (null si está vigente).
     */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function anulada(): bool
    {
        return $this->estado_acta === self::ESTADO_ANULADA;
    }

    public function previa(): BelongsTo
    {
        return $this->belongsTo(self::class, 'acta_previa_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getTipoAccionLabelAttribute(): string
    {
        return self::TIPOS_ACCION[$this->tipo_accion] ?? ucfirst((string) $this->tipo_accion);
    }
}
