<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Suscripcion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suscripciones';

    protected $fillable = [
        'nombre', 'fabricante', 'descripcion', 'cantidad_comprada', 'fecha_compra', 'fecha_vencimiento_global', 'observaciones', 'estado'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_vencimiento_global' => 'date',
    ];

    public function asignaciones()
    {
        return $this->hasMany(SuscripcionAsignacion::class, 'suscripcion_id');
    }

    public function historial()
    {
        return $this->hasMany(SuscripcionHistorial::class, 'suscripcion_id');
    }

    public function getCantidadAsignadaAttribute()
    {
        if (array_key_exists('cantidad_asignada_count', $this->attributes)) {
            return (int) $this->attributes['cantidad_asignada_count'];
        }

        return $this->asignaciones()->where('estado', 'Activa')->count();
    }

    public function getCantidadDisponibleAttribute()
    {
        return max(0, $this->cantidad_comprada - $this->cantidad_asignada);
    }
}
