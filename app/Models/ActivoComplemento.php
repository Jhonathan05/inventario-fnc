<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivoComplemento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activo_complementos';

    protected $fillable = [
        'equipo_id', 
        'catalogo_complemento_id', 
        'estado',
        'marca', 
        'modelo', 
        'serial', 
        'observaciones',
        'cantidad',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'cantidad' => 'integer',
    ];

    const ESTADOS = [
        'Disponible', 
        'Asignado', 
        'Dañado'
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function catalogoComplemento(): BelongsTo
    {
        return $this->belongsTo(CatalogoComplemento::class, 'catalogo_complemento_id');
    }

    // Accessor: nombre del complemento desde el catálogo
    public function getNombreAttribute(): string
    {
        return $this->catalogoComplemento?->nombre ?? 'Desconocido';
    }

    // Scopes para el Dashboard y Reportes
    public function scopeDisponibles($query)
    {
        return $query->where(function($q) {
            $q->whereNull('equipo_id')->orWhere('estado', 'Disponible');
        });
    }

    public function scopeAsignados($query)
    {
        return $query->whereNotNull('equipo_id')->where('estado', '!=', 'Disponible');
    }

    public function scopeDañados($query)
    {
        return $query->where('estado', 'Dañado');
    }

    public function scopeEnReparacion($query)
    {
        return $query->where('estado', 'En reparación');
    }

    public function scopeExtraviados($query)
    {
        return $query->where('estado', 'Extraviado');
    }

    public function scopeBajas($query)
    {
        return $query->where('estado', 'Dado de baja');
    }
}
