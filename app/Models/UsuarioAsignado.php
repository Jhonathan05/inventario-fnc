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

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
