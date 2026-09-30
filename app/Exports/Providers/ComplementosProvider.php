<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

class ComplementosProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Complementos',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $complementos = $equipo->complementos;
        
        if ($complementos->isEmpty()) {
            return [''];
        }

        $lineas = [];
        foreach ($complementos as $comp) {
            $linea = $comp->nombre;
            if ($comp->estado) {
                $linea .= " ({$comp->estado})";
            }
            if ($comp->serial) {
                $linea .= " [SN: {$comp->serial}]";
            }
            if ($comp->cantidad > 1) {
                $linea .= " x{$comp->cantidad}";
            }
            if ($comp->catalogoComplemento && $comp->catalogoComplemento->tipoRecursos->count() > 0) {
                $compatibilidad = $comp->catalogoComplemento->tipoRecursos->pluck('nombre')->join(', ');
                $linea .= " [Compatible con: {$compatibilidad}]";
            }
            $lineas[] = $linea;
        }

        return [
            implode(', ', $lineas),
        ];
    }

    public function getRelations(): array
    {
        return ['complementos.catalogoComplemento.tipoRecursos'];
    }
}
