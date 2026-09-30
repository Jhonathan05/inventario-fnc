<?php

namespace Tests\Unit;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Services\Importadores\CMDBMapperService;
use Tests\TestCase;

class PhpSpreadsheetImportCompatibilityTest extends TestCase
{
    public function test_column_indices_are_preserved_when_an_intermediate_cell_is_empty(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Serial', 'Marca', null, 'Modelo', 'Funcionario'],
            ['ABC123', 'HP', null, 'ProBook', 'JUAN'],
        ]);

        $headers = [];
        foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
            if ($cell->getValue() !== null && $cell->getValue() !== '') {
                $headers[Coordinate::columnIndexFromString($cell->getColumn()) - 1] = $cell->getValue();
            }
        }

        $this->assertSame('Serial', $headers[0]);
        $this->assertSame('Marca', $headers[1]);
        $this->assertArrayNotHasKey(2, $headers);
        $this->assertSame('Modelo', $headers[3]);
        $this->assertSame('Funcionario', $headers[4]);
    }

    public function test_identifiers_from_excel_are_normalized_for_matching(): void
    {
        $this->assertSame('10105258', CMDBMapperService::normalizeIdentifier(' 10105258.0 '));
        $this->assertSame('79912904', CMDBMapperService::normalizeIdentifier('79.912-904', true));
        $this->assertSame('ABC-123', CMDBMapperService::normalizeIdentifier(' abc-123 '));
    }

    public function test_responsible_column_does_not_change_a_normal_assignment_into_responsibility(): void
    {
        $row = [
            'cedula' => '79912904',
            'funcionario_asignado' => 'JAVIER LEONARDO MESA TRUJILLO',
            'funcionario_responsable' => 'ANALISTA TIC',
            'razon_del_estado' => 'ASIGNACIÓN NORMAL',
        ];

        $mapper = new CMDBMapperService();
        $mapper->initialize($row);

        $this->assertFalse($mapper->isResponsibilityRow($row));
    }

    public function test_explicit_cmdb_responsibility_reason_is_detected_without_using_column_presence(): void
    {
        $row = [
            'cedula' => '79912904',
            'nombres_y_apellidos' => 'JAVIER LEONARDO MESA TRUJILLO',
            'razon_del_estado' => 'ASIGNACIÓN RESPONSABLE',
        ];

        $mapper = new CMDBMapperService();
        $mapper->initialize($row);

        $this->assertTrue($mapper->isResponsibilityRow($row));
    }
}
