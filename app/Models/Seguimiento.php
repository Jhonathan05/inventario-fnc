<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    use HasFactory;

    protected $table = 'seguimientos';

    protected $fillable = [
        'seguible_id',
        'seguible_type',
        'user_id',
        'tipo_avance',
        'comentario',
        'is_system',
        'archivos',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'archivos' => 'array',
        'metadata' => 'array',
    ];

    public function seguible()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
