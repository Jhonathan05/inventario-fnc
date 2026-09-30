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

    // ── Constantes de tipos de acción ─────────────────────────────────────────

    const TIPO_ASIGNACION   = 'asignacion';
    const TIPO_REEMPLAZO    = 'reemplazo';
    const TIPO_RETIRO       = 'retiro';
    const TIPO_MANTENIMIENTO = 'mantenimiento';
    const TIPO_BAJA         = 'baja';
    const TIPO_RESTAURACION = 'restauracion';

    const TIPOS_ACCION = [
        self::TIPO_ASIGNACION    => 'Préstamo',
        self::TIPO_REEMPLAZO     => 'Reemplazo',
        self::TIPO_RETIRO        => 'Retiro de funcionario',
        self::TIPO_MANTENIMIENTO => 'Mantenimiento',
        self::TIPO_BAJA          => 'Retiro definitivo del equipo',
        self::TIPO_RESTAURACION  => 'Restauración',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
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
            'asignacion'    => 'success',
            'reemplazo'     => 'info',
            'retiro'        => 'secondary',
            'mantenimiento' => 'warning',
            'baja'          => 'danger',
            'restauracion'  => 'primary',
            default         => 'secondary',
        };
    }
}
