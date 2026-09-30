<?php

namespace App\Exports\Providers;

use App\Models\Equipo;
use Carbon\Carbon;

class AdministrativoProvider implements EquipoExportProviderInterface
{
    public function getHeadings(): array
    {
        return [
            'Fecha de Compra',
            'Fin de Garantía',
            'Estado Garantía',
        ];
    }

    public function map(Equipo $equipo): array
    {
        $estadoGarantia = 'N/A';
        if ($equipo->fin_garantia) {
            $estadoGarantia = Carbon::parse($equipo->fin_garantia)->isPast() ? 'VENCIDA' : 'VIGENTE';
        }

        return [
            $equipo->fecha_compra ? Carbon::parse($equipo->fecha_compra)->format('Y-m-d') : '',
            $equipo->fin_garantia ? Carbon::parse($equipo->fin_garantia)->format('Y-m-d') : '',
            $estadoGarantia,
        ];
    }

    public function getRelations(): array
    {
        return [];
    }
}
