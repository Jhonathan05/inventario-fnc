<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'identificacion',
        'identificacion_hash',
        'nombres',
        'apellidos',
        'cargo',
        'area',
        'departamento',
        'ciudad',
        'empresa_funcionario',
        'tipo_vinculacion',
        'estado',
        'seccional',
        'distrito',
    ];

    protected $casts = [
        'identificacion' => 'encrypted',
        'nombres' => 'encrypted',
        'cargo' => 'encrypted',
        'fecha_vencimiento' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function ($funcionario) {
            if ($funcionario->isDirty('identificacion') && $funcionario->identificacion) {
                // Normalizamos exactamente como en CMDBMapperService
                $identificacionNormalizada = trim(preg_replace('/[\x00-\x1F\x7F\x{00A0}]+/u', ' ', (string) $funcionario->identificacion));
                $identificacionNormalizada = strtoupper($identificacionNormalizada);
                $identificacionNormalizada = preg_replace('/[.\s-]+/', '', $identificacionNormalizada);
                
                $funcionario->identificacion_hash = hash_hmac('sha256', $identificacionNormalizada, config('app.key'));
            }
        });
    }

    public function getNombreCompletoAttribute()
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function licenciaAsignaciones()
    {
        return $this->hasMany(LicenciaAsignacion::class, 'funcionario_id');
    }

    public function equiposAsignados(): HasMany
    {
        return $this->hasMany(UsuarioAsignado::class, 'cedula', 'identificacion');
    }

    public function autorizacionesActivos(): HasMany
    {
        return $this->hasMany(AutorizacionActivo::class, 'funcionario_id');
    }
}
