<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    use HasFactory;

    protected $table = 'checklists';

    protected $fillable = [
        'equipo_id',
        'orden_trabajo',
        'observaciones',
        'cruce_av',
        'crece_software',
        'resultado',
        'tipo_aprobado',
        'fnc',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function getResponsableTiAttribute(): ?string
    {
        if ($this->relationLoaded('equipo')) {
            return $this->equipo?->responsable_nombre;
        }

        return $this->equipo()->value('responsable_nombre');
    }
}
