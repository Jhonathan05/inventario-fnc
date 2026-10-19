<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tarea del tablero. Las tareas manuales se persisten en esta tabla; las
 * derivadas de datos incompletos de la CMDB (equipos/funcionarios) se calculan
 * al vuelo en \App\Services\Dashboard\DashboardService y no viven aquí.
 */
class Tarea extends Model
{
    use SoftDeletes;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_HECHA = 'Hecha';

    public const PRIORIDADES = ['Alta', 'Media', 'Baja'];

    public const MODULOS = ['general', 'equipos', 'funcionarios'];

    protected $fillable = [
        'titulo',
        'descripcion',
        'modulo',
        'prioridad',
        'estado',
        'equipo_id',
        'funcionario_id',
        'created_by',
        'hecha_por',
        'hecha_en',
    ];

    protected $casts = [
        'hecha_en' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quienLaHizo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hecha_por');
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeOrdenPrioridad(Builder $q): Builder
    {
        return $q->orderByRaw("FIELD(prioridad, 'Alta', 'Media', 'Baja')")
            ->orderByDesc('created_at');
    }
}
