<?php

namespace App\Exports;

use App\Models\Asignacion;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AsignacionesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function query()
    {
        return Asignacion::with(['equipo.tipoRecurso', 'registradoPor'])
            ->orderBy('fecha_accion', 'desc');
    }

    public function title(): string
    {
        return 'Préstamos y Movimientos';
    }

    public function headings(): array
    {
        return [
            'ID',
            'FECHA ACCIÓN',
            'TIPO DE ACCIÓN',
            'SERIAL EQUIPO',
            'TIPO RECURSO',
            'MARCA Y MODELO',
            'USUARIO AFECTADO',
            'CÉDULA USUARIO',
            'MOTIVO / DETALLES',
            'REGISTRADO POR (SISTEMA)'
        ];
    }

    public function map($asignacion): array
    {
        $equipo = $asignacion->equipo;

        $row = [
            $asignacion->id,
            $asignacion->fecha_accion?->format('d/m/Y H:i:s'),
            $asignacion->tipo_accion_label,
            $equipo?->serial ?? 'N/A',
            $equipo?->tipoRecurso?->nombre ?? 'N/A',
            ($equipo?->marca ?? '') . ' ' . ($equipo?->modelo ?? ''),
            $asignacion->usuario_nombre ?? 'N/A',
            $asignacion->usuario_cedula ?? 'N/A',
            $asignacion->motivo,
            $asignacion->registradoPor?->name ?? 'N/A'
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
