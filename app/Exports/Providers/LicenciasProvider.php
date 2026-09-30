<?php

namespace App\Exports\Providers;

use App\Models\Equipo;
use Carbon\Carbon;

class LicenciasProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Total Licencias Asignadas',
            'Detalle de Licencias',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $asignaciones = $equipo->licenciaAsignaciones;
        $total = $asignaciones->count();
        $detalles = [];

        foreach ($asignaciones as $asignacion) {
            $licencia = $asignacion->licencia;
            if (!$licencia) continue;

            $estado = 'Activa';
            if ($licencia->fecha_vencimiento) {
                $estado = Carbon::parse($licencia->fecha_vencimiento)->isPast() ? 'Vencida' : 'Vence: ' . Carbon::parse($licencia->fecha_vencimiento)->format('Y-m-d');
            }
            $detalles[] = "{$licencia->nombre} ({$estado})";
        }

        return [
            $total,
            implode(' | ', $detalles),
        ];
    }

    public function getRelations(): array
    {
        return ['licenciaAsignaciones.licencia'];
    }
}
