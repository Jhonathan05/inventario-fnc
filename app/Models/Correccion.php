<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correccion — un faltante de información detectado en los activos
 * (pestaña "Correcciones" del módulo Importar).
 *
 * La importación nunca se bloquea: cada vacío, placeholder o valor fuera de
 * catálogo genera una corrección pendiente que se resuelve (o se justifica)
 * directamente desde la vista.
 *
 * @property string $seccion    identidad|ficha|custodia|catalogos
 * @property string $campo      serial, placa, estado_uso, custodia.cedula…
 * @property string $severidad  alta|media|baja
 * @property string $tipo_dato  texto|fecha|catalogo
 * @property string $estado     pendiente|resuelta|ignorada
 */
class Correccion extends Model
{
    protected $table = 'correcciones';

    protected $fillable = [
        'equipo_id',
        'funcionario_id',
        'seccion',
        'campo',
        'etiqueta',
        'valor_actual',
        'mensaje',
        'severidad',
        'tipo_dato',
        'catalogo',
        'estado',
        'valor_corregido',
        'justificacion',
        'resuelta_por',
        'resuelta_en',
        'origen',
    ];

    protected $casts = [
        'resuelta_en' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    /* ── Presentación ────────────────────────────────────────────── */

    public function getSeveridadLabelAttribute(): string
    {
        return match ($this->severidad) {
            'alta'  => 'Alta',
            'baja'  => 'Baja',
            default => 'Media',
        };
    }

    public function getSeveridadToneAttribute(): string
    {
        return match ($this->severidad) {
            'alta'  => 'danger',
            'baja'  => 'slate',
            default => 'warning',
        };
    }

    public function getEstadoToneAttribute(): string
    {
        return match ($this->estado) {
            'resuelta' => 'success',
            'ignorada' => 'slate',
            default    => 'danger',
        };
    }
}
