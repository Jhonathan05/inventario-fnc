<?php

namespace App\Exports\Providers;

use App\Models\Equipo;
use Illuminate\Support\Collection;

class CamposPersonalizadosProvider implements EquipoExportProviderInterface
{
    private Collection $campos;

    /**
     * @param Collection $campos Colección de modelos CampoPersonalizado ordenados y filtrados.
     */
    public function __construct(Collection $campos)
    {
        $this->campos = $campos;
    }

    public function getHeadings(): array
    {
        return $this->campos->pluck('nombre')->toArray();
    }

    public function map(Equipo $equipo): array
    {
        $valores = [];
        $equipoValores = $equipo->camposPersonalizadosValores->keyBy('campo_personalizado_id');

        foreach ($this->campos as $campo) {
            $valorStr = '';
            if ($equipoValores->has($campo->id)) {
                $valor = $equipoValores->get($campo->id)->valor;
                if (is_array($valor)) {
                    $valorStr = implode(', ', $valor);
                } else {
                    $valorStr = (string)$valor;
                }
            }
            $valores[] = $valorStr;
        }

        return $valores;
    }

    public function getRelations(): array
    {
        return ['camposPersonalizadosValores.campoPersonalizado'];
    }
}
