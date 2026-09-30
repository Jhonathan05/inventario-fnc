<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class HistorialProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Total Mantenimientos',
            'Fecha Último Mantenimiento',
            'Detalle Último Mantenimiento',
            'Costo Histórico Total',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $historial = $equipo->historialTecnico;
        $total = $historial->count();
        $costoTotal = $historial->sum('costo');
        
        $ultimo = $historial->first(); // Como está orderByDesc('fecha_evento'), el first() es el último cronológicamente.
        
        $fechaUltimo = $ultimo ? ($ultimo->fecha_evento ? $ultimo->fecha_evento->format('Y-m-d') : '') : '';
        $detalleUltimo = $ultimo ? "{$ultimo->tipo_mantenimiento} - {$ultimo->descripcion}" : '';

        return [
            $total,
            $fechaUltimo,
            $detalleUltimo,
            $costoTotal,
        ];
    }

    public function getRelations(): array
    {
        return ['historialTecnico'];
    }
}
