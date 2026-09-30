<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaSerial extends Model
{
    protected $table = 'licencia_seriales';

    protected $fillable = [
        'licencia_id',
        'serial',
        'estado',
        'observaciones',
    ];

    public function licencia()
    {
        return $this->belongsTo(Licencia::class);
    }

    public function asignacion()
    {
        return $this->hasOne(LicenciaAsignacion::class, 'licencia_serial_id');
    }
}
