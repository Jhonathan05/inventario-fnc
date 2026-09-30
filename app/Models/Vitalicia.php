<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vitalicia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vitalicias';

    protected $fillable = [
        'nombre', 'fabricante', 'tipo', 'descripcion', 'cantidad_comprada', 'fecha_compra', 'numero_factura', 'observaciones', 'estado'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
    ];

    public function asignaciones()
    {
        return $this->hasMany(VitaliciaAsignacion::class, 'vitalicia_id');
    }

    public function historial()
    {
        return $this->hasMany(VitaliciaHistorial::class, 'vitalicia_id');
    }

    public function getCantidadAsignadaAttribute()
    {
        if (array_key_exists('cantidad_asignada_count', $this->attributes)) {
            return (int) $this->attributes['cantidad_asignada_count'];
        }

        return $this->asignaciones()->count();
    }

    public function getCantidadDisponibleAttribute()
    {
        return max(0, $this->cantidad_comprada - $this->cantidad_asignada);
    }
}
