<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaHistorial extends Model
{
    use HasFactory;

    protected $table = 'licencia_historial';

    protected $fillable = [
        'fecha',
        'usuario_id',
        'accion',
        'licencia_nombre',
        'funcionario_nombre',
        'equipo_placa',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
