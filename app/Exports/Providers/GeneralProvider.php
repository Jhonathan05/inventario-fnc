<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class GeneralProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'ID Sistema',
            'Nombre Equipo',
            'Tipo de Activo',
            'Marca',
            'Modelo',
            'Serial',
            'Activo Fijo',
            'Placa',
            'Estado Operativo',
            'Razón de Estado',
            'Tiempo de Uso',
        ];
    }

    public function map(Equipo $equipo): array
    {
        return [
            $equipo->id,
            $equipo->nombre_equipo,
            $equipo->tipoRecurso ? $equipo->tipoRecurso->nombre : '',
            $equipo->marca,
            $equipo->modelo,
            $equipo->serial,
            $equipo->activo_fijo,
            $equipo->placa,
            $equipo->estado_operativo,
            $equipo->razon_estado,
            $equipo->tiempo_uso,
        ];
    }

    public function getRelations(): array
    {
        return ['tipoRecurso'];
    }
}
