<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaFirmadaVersion extends Model
{
    use HasFactory;

    protected $table = 'actas_firmadas_versions';

    protected $fillable = [
        'acta_firmada_id',
        'archivo_pdf',
        'version',
        'motivo_cambio',
        'user_id'
    ];

    public function actaFirmada(): BelongsTo
    {
        return $this->belongsTo(ActaFirmada::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
