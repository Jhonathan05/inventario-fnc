<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class ResponsableProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Responsable Nombre',
            'Responsable Cédula',
            'Responsable Cargo',
            'Responsable Área',
            'Responsable Ciudad',
            'Responsable Tipo Recurso',
            'Inicio Responsabilidad',
            'Fin Responsabilidad',
        ];
    }

    public function map(Equipo $equipo): array
    {
        return [
            $equipo->responsable_nombre,
            $equipo->responsable_cedula,
            $equipo->responsable_cargo,
            $equipo->responsable_area,
            $equipo->responsable_ciudad,
            $equipo->responsable_tipo_recurso,
            $equipo->fecha_inicio_responsable ? $equipo->fecha_inicio_responsable->format('Y-m-d') : '',
            $equipo->fecha_fin_responsable ? $equipo->fecha_fin_responsable->format('Y-m-d') : '',
        ];
    }

    public function getRelations(): array
    {
        return [];
    }
}
