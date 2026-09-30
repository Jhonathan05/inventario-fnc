<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialComplemento extends Model
{
    use HasFactory;

    protected $table = 'historial_complementos';

    protected $fillable = [
        'complemento_id',
        'evento',
        'usuario_id',
        'fecha',
        'activo_origen_id',
        'activo_destino_id',
        'estado_anterior',
        'estado_nuevo',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'observacion',
        'informacion_adicional',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'informacion_adicional' => 'array',
    ];

    public function complemento(): BelongsTo
    {
        return $this->belongsTo(ActivoComplemento::class, 'complemento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function activoOrigen(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'activo_origen_id');
    }

    public function activoDestino(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'activo_destino_id');
    }
}
