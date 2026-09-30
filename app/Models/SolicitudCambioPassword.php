<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudCambioPassword extends Model
{
    use HasFactory, SoftDeletes;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_ATENDIDA = 'Atendida';
    public const ESTADO_RECHAZADA = 'Rechazada';

    protected $table = 'solicitudes_cambio_password';

    protected $fillable = [
        'user_id',
        'email',
        'estado',
        'observacion',
        'ip',
        'user_agent',
        'administrador_id',
        'fecha_atencion',
    ];

    protected $casts = [
        'fecha_atencion' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrador_id');
    }
}
