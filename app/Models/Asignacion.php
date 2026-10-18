<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asignacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asignaciones';

    protected $fillable = [
        'equipo_id',
        'acta_id',
        'usuario_nombre',
        'usuario_cedula',
        'usuario_empresa_propietaria',
        'usuario_dependencia',
        'usuario_fuente_recurso',
        'usuario_empresa_funcionario',
        'usuario_tipo_vinculacion',
        'usuario_shortname',
        'usuario_departamento',
        'usuario_ciudad',
        'usuario_cargo',
        'usuario_area',
        'usuario_piso',
        'usuario_distrito',
        'usuario_seccional',
        'tipo_accion',
        'motivo',
        'observaciones',
        'entregado_por',
        'user_id',
        'fecha_accion',
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
    ];

    // ── Constantes de tipos de acción (catálogo v3 del formato de novedad) ────

    const TIPO_INVENTARIO_FISICO = 'inventario_fisico';
    const TIPO_TRASLADO = 'traslado';
    const TIPO_CAMBIO_RESPONSABLE = 'cambio_responsable';
    const TIPO_RETIRO_DEFINITIVO = 'retiro_definitivo';
    const TIPO_PRESTAMO = 'prestamo';
    const TIPO_REPARACION = 'reparacion';
    const TIPO_RESTAURACION = 'restauracion'; // alias legacy: salida de reparación

    // Alias legacy para compatibilidad con el flujo por equipo
    const TIPO_ASIGNACION = self::TIPO_PRESTAMO;
    const TIPO_REEMPLAZO = self::TIPO_CAMBIO_RESPONSABLE;
    const TIPO_RETIRO = self::TIPO_RETIRO_DEFINITIVO;
    const TIPO_MANTENIMIENTO = self::TIPO_REPARACION;
    const TIPO_BAJA = self::TIPO_RETIRO_DEFINITIVO;

    const TIPOS_ACCION = [
        self::TIPO_INVENTARIO_FISICO => 'Inventario Físico',
        self::TIPO_TRASLADO => 'Traslado',
        self::TIPO_CAMBIO_RESPONSABLE => 'Cambio de responsable',
        self::TIPO_RETIRO_DEFINITIVO => 'Retiro definitivo',
        self::TIPO_PRESTAMO => 'Préstamo',
        self::TIPO_REPARACION => 'Reparación',
        self::TIPO_RESTAURACION => 'Salida de reparación',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function acta(): BelongsTo
    {
        return $this->belongsTo(Acta::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Etiqueta legible del tipo de acción.
     */
    public function getTipoAccionLabelAttribute(): string
    {
        return self::TIPOS_ACCION[$this->tipo_accion] ?? ucfirst($this->tipo_accion);
    }

    /**
     * Color Bootstrap para el badge del tipo de acción.
     */
    public function getTipoAccionColorAttribute(): string
    {
        return match ($this->tipo_accion) {
            'inventario_fisico', 'prestamo' => 'success',
            'cambio_responsable' => 'info',
            'traslado' => 'secondary',
            'reparacion' => 'warning',
            'retiro_definitivo' => 'danger',
            default => 'secondary',
        };
    }
}
