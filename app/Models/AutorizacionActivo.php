<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutorizacionActivo extends Model
{
    use HasFactory;

    public const ESTADO_CARGADA = 'cargada';
    public const ESTADO_CONSUMIDA = 'consumida';
    public const ESTADO_ANULADA = 'anulada';

    protected $table = 'autorizaciones_activos';

    protected $fillable = [
        'funcionario_id',
        'equipo_id',
        'asignacion_id',
        'cedula',
        'nombre_funcionario',
        'archivo',
        'mime_type',
        'tamano_bytes',
        'estado',
        'consumida_en',
        'consumida_por_user_id',
        'anulada_en',
        'anulada_por_user_id',
        'motivo_anulacion',
        'user_id',
    ];

    protected $casts = [
        'consumida_en' => 'datetime',
        'anulada_en' => 'datetime',
    ];

    public function scopeDisponibles($query)
    {
        return $query->where('estado', self::ESTADO_CARGADA);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
