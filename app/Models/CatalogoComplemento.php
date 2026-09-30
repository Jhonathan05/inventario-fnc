<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoComplemento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'catalogo_complementos';

    protected $fillable = [
        'nombre',
        'requiere_serial',
        'obligatorio',
        'usa_estado',
        'cantidad_default',
        'activo',
    ];

    protected $casts = [
        'requiere_serial' => 'boolean',
        'obligatorio' => 'boolean',
        'usa_estado' => 'boolean',
        'cantidad_default' => 'integer',
    ];

    public function tipoRecursos(): BelongsToMany
    {
        return $this->belongsToMany(TipoRecurso::class, 'tipo_recurso_complemento')
                    ->withPivot('orden')
                    ->withTimestamps();
    }

    public function instancias(): HasMany
    {
        return $this->hasMany(ActivoComplemento::class, 'catalogo_complemento_id');
    }
}
