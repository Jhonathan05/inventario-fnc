<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class DatosTecnicosProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Procesador',
            'Memoria RAM',
            'Disco Duro',
            'Sistema Operativo',
        ];
    }

    public function map(Equipo $equipo): array
    {
        return [
            $equipo->procesador,
            $equipo->ram,
            $equipo->disco,
            $equipo->sistema_operativo,
        ];
    }

    public function getRelations(): array
    {
        return [];
    }
}
