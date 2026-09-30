<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Licencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'tipo_licencia',
        'cantidad_maxima',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_compra',
        'fecha_renovacion',
        'correo_compra',
        'estado',
        'observaciones',
        'requiere_correo',
        'correo_asociado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_compra' => 'date',
        'fecha_renovacion' => 'date',
        'cantidad_maxima' => 'integer',
    ];

    public function seriales(): HasMany
    {
        return $this->hasMany(LicenciaSerial::class, 'licencia_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(LicenciaAsignacion::class, 'licencia_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(LicenciaHistorial::class, 'licencia_nombre', 'nombre'); // Vinculo lógico por nombre
    }

    /**
     * Devuelve la cantidad de cupos actualmente asignados de forma activa.
     */
    public function getCuposAsignadosAttribute(): int
    {
        if (array_key_exists('cupos_asignados_count', $this->attributes)) {
            return (int) $this->attributes['cupos_asignados_count'];
        }

        return $this->asignaciones()->where('estado', 'Activa')->count();
    }

    /**
     * Devuelve la cantidad de cupos disponibles.
     */
    public function getCuposDisponiblesAttribute(): int
    {
        return max(0, $this->cantidad_maxima - $this->cupos_asignados);
    }

    /**
     * Indica si quedan cupos disponibles para esta licencia.
     */
    public function getTieneCuposAttribute(): bool
    {
        return $this->cupos_disponibles > 0;
    }
}
