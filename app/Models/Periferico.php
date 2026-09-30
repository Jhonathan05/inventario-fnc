<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Periferico extends Model
{
    use HasFactory;

    protected $table = 'perifericos';

    protected $fillable = [
        'equipo_id',
        'telefono',
        'teclado',
        'mouse',
        'camara',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
