<?php

namespace Tests\Feature;

use App\Imports\EquiposImport;
use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\UsuarioAsignado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CmdbImportRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'ACTIVO FIJO',
        'PLACA',
        'SERIAL',
        'NOMBRES Y APELLIDOS',
        'CEDULA DE FUNCIONARIO / CONTRATISTA',
        'TIPO',
        'ESTADO OPERATIVO',
        'RAZÓN DEL ESTADO',
        'FUNCIONARIO RESPONSABLE',
        'RESPONSABLE DEL ACTIVO CEDULA',
    ];

    public function test_normal_cmdb_assignment_is_idempotent(): void
    {
        $tipo = TipoRecurso::create(['nombre' => 'EQUIPO ESCRITORIO']);
        $equipo = Equipo::create([
            'tipo_recurso_id' => $tipo->id,
            'activo_fijo' => '10105258',
            'placa' => '10105258',
            'serial' => '1NFPT64',
            'marca' => 'DELL',
            'modelo' => 'OPTIPLEX',
            'nombre_equipo' => 'ESC-122706',
            'estado_operativo' => 'asignado',
        ]);

        $row = [
            '10105258', '10105258', '1NFPT64', 'JAVIER LEONARDO MESA TRUJILLO',
            '79912904.0', 'EQUIPO ESCRITORIO', 'ASIGNADO', 'ASIGNACIÓN NORMAL', 'ANALISTA TIC',
        ];

        $this->runImport($row);
        $this->runImport($row);

        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseCount('funcionarios', 1);
        $this->assertDatabaseCount('usuario_asignados', 1);
        $this->assertDatabaseHas('usuario_asignados', [
            'equipo_id' => $equipo->id,
            'cedula' => '79912904',
            'nombre' => 'JAVIER LEONARDO MESA TRUJILLO',
        ]);
        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_operativo' => 'asignado',
        ]);
    }

    private function runImport(array $row): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cmdb_relationship_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([$this->headers, $row]);
        (new Xlsx($spreadsheet))->save($file);

        try {
            $import = new EquiposImport($file, 'RESPONSABLE INSTITUCIONAL');
            $import->model($row);
        } finally {
            @unlink($file);
        }
    }
}
