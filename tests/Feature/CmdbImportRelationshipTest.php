<?php

namespace Tests\Feature;

use App\Imports\EquiposImport;
use App\Models\AsignacionResponsabilidad;
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

    public function test_normal_cmdb_assignment_closes_a_previous_responsibility_and_is_idempotent(): void
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
        $responsabilidad = AsignacionResponsabilidad::create([
            'equipo_id' => $equipo->id,
            'nombre_usuario' => 'RESPONSABLE ANTERIOR',
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'activa',
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
        $this->assertDatabaseHas('asignaciones_responsabilidad', [
            'id' => $responsabilidad->id,
            'estado' => 'finalizada',
        ]);
        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id,
            'estado_operativo' => 'asignado',
        ]);
    }

    /**
     * "ASIGNACIÓN RESPONSABLE" con una cédula de responsable distinta a la del
     * funcionario asignado: se crea la asignación normal (`usuario_asignados`) y
     * ADEMÁS la capa de responsabilidad (`asignaciones_responsabilidad`).
     *
     * Ver app/Imports/EquiposImport.php: "La AsignacionResponsabilidad es una
     * capa ADICIONAL (como en la UI), NO un reemplazo de la asignación normal."
     */
    public function test_explicit_responsibility_adds_the_special_relation_on_top_of_the_normal_assignment(): void
    {
        $row = [
            '10105259', '10105259', 'SERIAL-RESP-1', 'FUNCIONARIO ASIGNADO',
            '79912905', 'EQUIPO ESCRITORIO', 'ASIGNADO', 'ASIGNACIÓN RESPONSABLE', '',
            '79912906', // responsable distinto al funcionario asignado
        ];

        $this->runImport($row);
        $this->runImport($row); // idempotente

        $equipo = Equipo::where('activo_fijo', '10105259')->firstOrFail();

        // La asignación normal siempre existe: es la capa base.
        $this->assertDatabaseHas('usuario_asignados', [
            'equipo_id' => $equipo->id,
            'cedula' => '79912905',
        ]);

        // La responsabilidad es una capa adicional, no un reemplazo.
        $this->assertDatabaseCount('asignaciones_responsabilidad', 1);
        $this->assertDatabaseHas('asignaciones_responsabilidad', [
            'equipo_id' => $equipo->id,
            'estado' => 'activa',
            'documento' => '79912905',
            'responsable_cedula' => '79912906',
        ]);
    }

    /**
     * "ASIGNACIÓN RESPONSABLE" sin un responsable distinto se degrada a
     * asignación normal: no se crea la capa de responsabilidad.
     */
    public function test_responsibility_flag_without_a_distinct_manager_is_a_normal_assignment(): void
    {
        $row = [
            '10105260', '10105260', 'SERIAL-RESP-2', 'FUNCIONARIO ASIGNADO',
            '79912907', 'EQUIPO ESCRITORIO', 'ASIGNADO', 'ASIGNACIÓN RESPONSABLE', '',
        ];

        $this->runImport($row);

        $equipo = Equipo::where('activo_fijo', '10105260')->firstOrFail();

        $this->assertDatabaseHas('usuario_asignados', [
            'equipo_id' => $equipo->id,
            'cedula' => '79912907',
        ]);
        $this->assertDatabaseMissing('asignaciones_responsabilidad', [
            'equipo_id' => $equipo->id,
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
