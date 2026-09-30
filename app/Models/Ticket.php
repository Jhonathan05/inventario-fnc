<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'titulo',
        'tipo',
        'prioridad',
        'descripcion',
        'estado',
        'funcionario_id',
        'equipo_id',
        'user_id',
        'archivos',
        
        'fecha_solicitud',

        // Diagnóstico
        'diagnostico_inicial',
        'causa_probable',
        'observaciones_tecnicas',
        'fecha_diagnostico',
        'diagnostico_user_id',
        
        // Solución
        'solucion_aplicada',
        'fecha_solucion',
        'fecha_cierre',
        'tiempo_invertido',
        'observaciones_finales',
    ];

    protected $casts = [
        'archivos' => 'array',
        'fecha_solicitud' => 'datetime',
        'fecha_diagnostico' => 'datetime',
        'fecha_solucion' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function diagnosticoPor()
    {
        return $this->belongsTo(User::class, 'diagnostico_user_id');
    }

    public function seguimientos()
    {
        return $this->morphMany(Seguimiento::class, 'seguible')->orderBy('created_at', 'asc');
    }
}
