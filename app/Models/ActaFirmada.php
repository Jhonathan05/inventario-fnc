<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActaFirmada extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actas_firmadas';

    protected $fillable = [
        'numero_acta',
        'tipo_acta',
        'fecha_documento',
        'observaciones',
        'archivo_pdf',
        'user_id'
    ];

    protected $casts = [
        'fecha_documento' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ActaFirmadaVersion::class)->orderBy('version', 'desc');
    }
}
