<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampoPersonalizadoOpcion extends Model
{
    use HasFactory;

    protected $table = 'campo_personalizado_opciones';

    protected $fillable = [
        'campo_personalizado_id',
        'valor',
        'orden',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function campoPersonalizado(): BelongsTo
    {
        return $this->belongsTo(CampoPersonalizado::class);
    }
}
