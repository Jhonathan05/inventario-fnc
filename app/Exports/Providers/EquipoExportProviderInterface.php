<?php

namespace App\Exports\Providers;

use App\Models\Equipo;

interface EquipoExportProviderInterface
{
    /**
     * Retorna los encabezados (columnas) que este proveedor aporta al Excel.
     */
    public function getHeadings(): array;

    /**
     * Extrae y mapea la información del equipo para este proveedor.
     */
    public function map(Equipo $equipo): array;

    /**
     * Retorna las relaciones que deben cargarse (Eager Loading) para evitar N+1.
     */
    public function getRelations(): array;
}
