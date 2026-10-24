<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioAsignado extends Model
{
    use HasFactory;

    protected $table = 'usuario_asignados';

    protected $fillable = [
        'equipo_id',
        'funcionario_id',
        'acta_id',
        'desde',
        'hasta',
        'modalidad',
        'nombre',
        'cedula',
        'empresa_propietaria',
        'dependencia',
        'fuente_recurso',
        'empresa_funcionario',
        'tipo_vinculacion',
        'shortname',
        'departamento',
        'ciudad',
        'cargo',
        'area',
        'piso',
        'distrito',
        'seccional',
    ];

    protected $casts = [
        'desde' => 'datetime',
        'hasta' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function acta(): BelongsTo
    {
        return $this->belongsTo(Acta::class);
    }
}
