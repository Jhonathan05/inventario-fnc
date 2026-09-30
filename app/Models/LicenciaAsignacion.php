<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenciaAsignacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'licencia_asignaciones';

    protected $fillable = [
        'licencia_id',
        'licencia_serial_id',
        'equipo_id',
        'funcionario_id',
        'fecha_asignacion',
        'fecha_vencimiento',
        'correo_activacion',
        'estado',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(LicenciaSerial::class, 'licencia_serial_id');
    }
}
