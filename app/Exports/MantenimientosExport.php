<?php

namespace App\Exports;

use App\Models\HistorialTecnico;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MantenimientosExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function query()
    {
        return HistorialTecnico::with(['equipo.tipoRecurso', 'registradoPor'])
            ->orderBy('fecha_evento', 'desc');
    }

    public function title(): string
    {
        return 'Historial de Mantenimientos';
    }

    public function headings(): array
    {
        return [
            'ID',
            'FECHA DE EVENTO',
            'TIPO DE EVENTO',
            'SERIAL EQUIPO',
            'TIPO RECURSO',
            'RESPONSABLE (TÉCNICO/USUARIO)',
            'DESCRIPCIÓN',
            'MOTIVO',
            'OBSERVACIONES',
            'REGISTRADO POR (SISTEMA)'
        ];
    }

    public function map($historial): array
    {
        $equipo = $historial->equipo;

        $row = [
            $historial->id,
            $historial->fecha_evento?->format('d/m/Y'),
            $historial->tipo_evento_label,
            $equipo?->serial ?? 'N/A',
            $equipo?->tipoRecurso?->nombre ?? 'N/A',
            $historial->usuario_responsable ?? 'N/A',
            $historial->descripcion,
            $historial->motivo ?? '',
            $historial->observaciones ?? '',
            $historial->registradoPor?->name ?? 'N/A'
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
