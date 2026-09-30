<?php

namespace App\Exports\Providers;

class AsignacionResponsabilidadProvider implements EquipoExportProviderInterface
{
    public function getRelations(): array
    {
        return ['asignacionResponsabilidadActiva'];
    }

    public function getHeadings(): array
    {
        return [
            'TIPO DE ASIGNACIÓN',
            'RESPONSABLE ADMINISTRATIVO',
            'USUARIO ASIGNADO (RESPONSABILIDAD)',
            'PROYECTO (RESPONSABILIDAD)',
            'EMPRESA (RESPONSABILIDAD)',
            'CARGO (RESPONSABILIDAD)',
            'FECHA INICIO (RESPONSABILIDAD)',
            'FECHA FINAL (RESPONSABILIDAD)',
            'OBSERVACIONES (RESPONSABILIDAD)'
        ];
    }

    public function map($equipo): array
    {
        $asignacion = $equipo->asignacionResponsabilidadActiva;

        if (!$asignacion) {
            return [
                $equipo->usuarioAsignado ? 'ASIGNACIÓN NORMAL' : 'SIN ASIGNAR',
                mb_strtoupper((string) $equipo->responsable_nombre),
                '', // Usuario Asignado
                '', // Proyecto
                '', // Empresa
                '', // Cargo
                '', // Fecha Inicio
                '', // Fecha Final
                '', // Observaciones
            ];
        }

        return [
            'ASIGNACIÓN BAJO RESPONSABILIDAD',
            mb_strtoupper((string) $equipo->responsable_nombre),
            mb_strtoupper((string) $asignacion->nombre_usuario),
            mb_strtoupper((string) $asignacion->proyecto),
            mb_strtoupper((string) $asignacion->empresa),
            mb_strtoupper((string) $asignacion->cargo),
            optional($asignacion->fecha_inicio)->format('Y-m-d') ?? '',
            optional($asignacion->fecha_final_estimada)->format('Y-m-d') ?? '',
            mb_strtoupper((string) $asignacion->observaciones)
        ];
    }
}
