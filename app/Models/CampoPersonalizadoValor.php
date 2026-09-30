<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampoPersonalizadoValor extends Model
{
    use HasFactory;

    protected $table = 'campo_personalizado_valores';

    protected $fillable = [
        'campo_personalizado_id',
        'entidad_id',
        'valor'
    ];

    public function campoPersonalizado(): BelongsTo
    {
        return $this->belongsTo(CampoPersonalizado::class);
    }
}
