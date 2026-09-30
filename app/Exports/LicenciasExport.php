<?php

namespace App\Exports;

use App\Models\Licencia;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LicenciasExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function query()
    {
        $query = Licencia::query();

        if (!empty($this->filtros['buscar'])) {
            $buscar = $this->filtros['buscar'];
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('tipo_licencia', 'like', "%{$buscar}%")
                  ->orWhere('estado', 'like', "%{$buscar}%");
            });
        }

        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
        }

        return $query->orderBy('nombre');
    }

    public function title(): string
    {
        return 'Reporte de Licencias';
    }

    public function headings(): array
    {
        return [
            'ID',
            'NOMBRE',
            'TIPO DE LICENCIA',
            'ESTADO',
            'CANTIDAD MÁXIMA',
            'CUPOS ASIGNADOS',
            'CUPOS DISPONIBLES',
            'FECHA INICIO',
            'FECHA VENCIMIENTO',
            'FECHA COMPRA',
            'FECHA RENOVACIÓN',
            'CORREO COMPRA',
            'OBSERVACIONES',
        ];
    }

    public function map($licencia): array
    {
        $row = [
            $licencia->id,
            $licencia->nombre,
            $licencia->tipo_licencia,
            $licencia->estado,
            $licencia->cantidad_maxima,
            $licencia->cupos_asignados,
            $licencia->cupos_disponibles,
            $licencia->fecha_inicio ? $licencia->fecha_inicio->format('d/m/Y') : 'N/A',
            $licencia->fecha_vencimiento ? $licencia->fecha_vencimiento->format('d/m/Y') : 'N/A',
            $licencia->fecha_compra ? $licencia->fecha_compra->format('d/m/Y') : 'N/A',
            $licencia->fecha_renovacion ? $licencia->fecha_renovacion->format('d/m/Y') : 'N/A',
            $licencia->correo_compra ?: 'N/A',
            $licencia->observaciones,
        ];

        return array_map(function($item) {
            return is_string($item) ? mb_strtoupper($item, 'UTF-8') : $item;
        }, $row);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
