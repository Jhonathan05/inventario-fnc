<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestamo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipo_id',
        'persona_nombre',
        'persona_documento',
        'user_id',
        'fecha_inicio',
        'fecha_devolucion_prevista',
        'fecha_devolucion_real',
        'duracion',
        'estado',
        'motivo',
        'observaciones',
        'usuario_devolucion_id',
        'estado_fisico_devolucion',
        'observaciones_devolucion',
        'complementos_devueltos',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_devolucion_prevista' => 'datetime',
        'fecha_devolucion_real' => 'datetime',
        'complementos_devueltos' => 'array',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function devueltoPor()
    {
        return $this->belongsTo(User::class, 'usuario_devolucion_id');
    }
    
    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'Pendiente' => 'secondary',
            'Activo'    => 'primary',
            'Vencido'   => 'danger',
            'Devuelto'  => 'success',
            'Cancelado' => 'dark',
            default     => 'secondary',
        };
    }
}
