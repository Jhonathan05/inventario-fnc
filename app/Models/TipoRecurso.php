<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class TipoRecurso extends Model
{
    use HasFactory;

    protected $table = 'tipo_recursos';

    protected $fillable = ['nombre'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function complementosDefinidos(): BelongsToMany
    {
        return $this->belongsToMany(CatalogoComplemento::class, 'tipo_recurso_complemento')
                    ->withPivot('orden', 'obligatorio')
                    ->withTimestamps()
                    ->orderBy('tipo_recurso_complemento.orden');
    }

    /**
     * Devuelve el prefijo de 3 letras estandarizado para la identificación interna.
     */
    public function getPrefijoAttribute(): string
    {
        $nombreLower = mb_strtolower(trim($this->nombre));
        
        $diccionario = [
            'equipo portatil'    => 'POR', // Portátil
            'equipo escritorio'  => 'ESC', // Escritorio
            'equipo todo en uno' => 'TEU', // Todo En Uno
            'equipo micro'       => 'MIC', // Micro
            'monitor'            => 'MON',
            'impresora'          => 'IMP', // Impresora
            'disco solido'       => 'SSD', // Solid State Drive (Universal)
            'teclado'            => 'TEC',
            'mouse'              => 'MOU', // Mouse
            'telefono'           => 'TEL',
            'tablet'             => 'TAB',
            'servidor'           => 'SER', // Servidor
            'switch'             => 'SWI',
            'router'             => 'ROU', // Router
            'camara'             => 'CAM',
            'escaner'            => 'ESN', // Escáner
            'tv'                 => 'TLV', // TV
        ];

        if (isset($diccionario[$nombreLower])) {
            return $diccionario[$nombreLower];
        }

        // Fallback: 3 primeras letras en mayúscula
        $prefijoFallback = mb_substr($nombreLower, 0, 3);
        if (mb_strlen($prefijoFallback) < 3) {
            $prefijoFallback = str_pad($prefijoFallback, 3, 'X');
        }

        return mb_strtoupper($prefijoFallback);
    }
}
