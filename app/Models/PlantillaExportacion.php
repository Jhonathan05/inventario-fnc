<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaExportacion extends Model
{
    use HasFactory;

    protected $table = 'plantillas_exportacion';

    protected $fillable = [
        'nombre',
        'modulo',
        'user_id',
        'configuracion_json'
    ];

    protected $casts = [
        'configuracion_json' => 'array',
    ];

    public function getColumnasEstandarAttribute(): array
    {
        return (array) data_get($this->configuracion_json, 'columnas_estandar', []);
    }

    public function getColumnasPersonalizadasAttribute(): array
    {
        return (array) data_get($this->configuracion_json, 'columnas_personalizadas', []);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
