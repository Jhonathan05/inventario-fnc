<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class PerifericosProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Tiene Teclado',
            'Tiene Mouse',
            'Tiene Cámara',
            'Tiene Teléfono',
            'Tiene Cargador',
            'Tiene Guaya',
            'Tiene Maletín',
            'Bases / Otros',
            'Observaciones Periféricos',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $periferico = $equipo->periferico;
        return [
            $periferico ? $periferico->teclado : 'NO',
            $periferico ? $periferico->mouse : 'NO',
            $periferico ? $periferico->camara : 'NO',
            $periferico ? $periferico->telefono : 'NO',
            $periferico ? $periferico->cargador : 'NO',
            $periferico ? $periferico->guaya : 'NO',
            $periferico ? $periferico->maletin : 'NO',
            $periferico ? $periferico->bases_otros : 'NO',
            $periferico ? $periferico->observaciones : '',
        ];
    }

    public function getRelations(): array
    {
        return ['periferico'];
    }
}
