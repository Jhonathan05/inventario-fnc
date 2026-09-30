<?php

namespace App\Exports;

use App\Models\Equipo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class GarantiasExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function query()
    {
        return Equipo::with(['tipoRecurso'])
            ->whereNotNull('fin_garantia')
            ->orderBy('fin_garantia', 'asc');
    }

    public function title(): string
    {
        return 'Garantías de Equipos';
    }

    public function headings(): array
    {
        return [
            'TIPO DE RECURSO',
            'SERIAL',
            'PLACA / ACTIVO FIJO',
            'MARCA',
            'MODELO',
            'FECHA COMPRA',
            'FIN DE GARANTÍA',
            'DÍAS RESTANTES',
            'ESTADO GARANTÍA',
            'PROVEEDOR / NOTAS'
        ];
    }

    public function map($equipo): array
    {
        $diasRestantes = now()->startOfDay()->diffInDays($equipo->fin_garantia->startOfDay(), false);
        
        $estadoGarantia = 'Vigente';
        if ($diasRestantes < 0) {
            $estadoGarantia = 'Vencida';
        } elseif ($diasRestantes <= 30) {
            $estadoGarantia = 'Próxima a vencer';
        }

        $row = [
            $equipo->tipoRecurso?->nombre ?? 'N/A',
            $equipo->serial,
            $equipo->placa ?? $equipo->activo_fijo,
            $equipo->marca,
            $equipo->modelo,
            $equipo->fecha_compra?->format('d/m/Y') ?? 'N/A',
            $equipo->fin_garantia?->format('d/m/Y'),
            $diasRestantes,
            $estadoGarantia,
            $equipo->proveedor_compra ?? '' // Si no existe, dejar en blanco
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
