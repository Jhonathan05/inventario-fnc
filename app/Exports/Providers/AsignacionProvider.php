<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class AsignacionProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Funcionario Asignado',
            'Cédula Funcionario',
            'Cargo Funcionario',
            'Área Funcionario',
            'Dependencia Funcionario',
            'Empresa Propietaria',
            'Empresa Funcionario',
            'Tipo Vinculación',
            'Ciudad Funcionario',
            'Departamento Funcionario',
            'Shortname',
            'Piso',
            'Distrito',
            'Seccional',
            'Fuente Recurso',
            'Estado Asignación',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $asignado = $equipo->usuarioAsignado;
        return [
            $asignado ? $asignado->nombre : '',
            $asignado ? $asignado->cedula : '',
            $asignado ? $asignado->cargo : '',
            $asignado ? $asignado->area : '',
            $asignado ? $asignado->dependencia : '',
            $asignado ? $asignado->empresa_propietaria : '',
            $asignado ? $asignado->empresa_funcionario : '',
            $asignado ? $asignado->tipo_vinculacion : '',
            $asignado ? $asignado->ciudad : '',
            $asignado ? $asignado->departamento : '',
            $asignado ? $asignado->shortname : '',
            $asignado ? $asignado->piso : '',
            $asignado ? $asignado->distrito : '',
            $asignado ? $asignado->seccional : '',
            $asignado ? $asignado->fuente_recurso : '',
            $asignado ? $asignado->estado : '',
        ];
    }

    public function getRelations(): array
    {
        return ['usuarioAsignado'];
    }
}
