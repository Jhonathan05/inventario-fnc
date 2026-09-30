<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionResponsabilidad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asignaciones_responsabilidad';

    protected $fillable = [
        'equipo_id',
        'user_id',
        'responsable_id',
        'responsable_nombre',
        'responsable_cedula',
        'nombre_usuario',
        'documento',
        'empresa',
        'cargo',
        'proyecto',
        'area',
        'correo',
        'telefono',
        'tipo_usuario',

        'fecha_inicio',
        'fecha_final_estimada',
        'observaciones',
        'estado',
        'fecha_final_real',
        'motivo_finalizacion',
        'observaciones_finales',
        'finalizado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_final_estimada' => 'date',
        'fecha_final_real' => 'date',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function finalizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por');
    }
}
