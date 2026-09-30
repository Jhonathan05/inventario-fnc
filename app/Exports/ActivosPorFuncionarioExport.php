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

class ActivosPorFuncionarioExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function query()
    {
        // Solo equipos que tienen un usuario asignado, ordenados por nombre de usuario
        return Equipo::has('usuarioAsignado')
            ->with(['tipoRecurso', 'usuarioAsignado'])
            ->join('usuario_asignados', 'equipos.id', '=', 'usuario_asignados.equipo_id')
            ->orderBy('usuario_asignados.nombre')
            ->select('equipos.*');
    }

    public function title(): string
    {
        return 'Activos por Funcionario';
    }

    public function headings(): array
    {
        return [
            'CÉDULA',
            'NOMBRES Y APELLIDOS',
            'CARGO',
            'ÁREA',
            'DEPARTAMENTO',
            'CIUDAD',
            'EMPRESA FUNCIONARIO',
            'TIPO VINCULACIÓN',
            'TIPO DE RECURSO',
            'SERIAL',
            'PLACA / ACTIVO FIJO',
            'MARCA',
            'MODELO',
            'ESTADO OPERATIVO',
            'FECHA DE PRÉSTAMO (SISTEMA)'
        ];
    }

    public function map($equipo): array
    {
        $usuario = $equipo->usuarioAsignado;

        $row = [
            $usuario?->cedula,
            $usuario?->nombre,
            $usuario?->cargo,
            $usuario?->area,
            $usuario?->departamento,
            $usuario?->ciudad,
            $usuario?->empresa_funcionario,
            $usuario?->tipo_vinculacion,
            $equipo->tipoRecurso?->nombre ?? 'N/A',
            $equipo->serial,
            $equipo->placa ?? $equipo->activo_fijo,
            $equipo->marca,
            $equipo->modelo,
            ucfirst($equipo->estado_operativo),
            $usuario?->created_at?->format('d/m/Y H:i:s')
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
