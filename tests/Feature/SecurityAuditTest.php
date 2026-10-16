<?php

namespace Tests\Feature;

use App\Models\CatalogoComplemento;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\Ticket;
use App\Models\TipoRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    /*
     * Se usa RefreshDatabase sobre la base de datos de pruebas
     * (`inventario_testing`, ver phpunit.xml). `Tests\TestCase` aborta antes de
     * migrar si la conexión no apunta a una base de datos de pruebas, así que
     * es imposible que esta suite borre datos de desarrollo o producción.
     */
    use RefreshDatabase;

    private TipoRecurso $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        // Asegurarse de limpiar rate limiters para la prueba
        RateLimiter::clear('equipos.importar');
        RateLimiter::clear('equipos.exportar');

        // Crear permisos básicos para las pruebas
        Permission::firstOrCreate(['name' => 'mesaayuda.ver']);
        Permission::firstOrCreate(['name' => 'mesaayuda.eliminar']);
        Permission::firstOrCreate(['name' => 'mesaayuda.editar']);
        Permission::firstOrCreate(['name' => 'equipos.crear']);
        Permission::firstOrCreate(['name' => 'equipos.importar']);

        $this->tipo = TipoRecurso::create(['nombre' => 'EQUIPO ESCRITORIO']);
    }

    private function crearEquipo(): Equipo
    {
        return Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'serial' => 'SN-' . uniqid(),
            'marca' => 'DELL',
            'modelo' => 'OPTIPLEX',
            'nombre_equipo' => 'EQ-' . uniqid(),
            'estado_operativo' => 'disponible',
        ]);
    }

    private function crearTicket(): Ticket
    {
        $funcionario = Funcionario::create([
            'identificacion' => '79912904',
            'nombres' => 'FUNCIONARIO DE PRUEBA',
        ]);

        return Ticket::create([
            'titulo' => 'Ticket de prueba',
            'tipo' => 'Incidencia',
            'prioridad' => 'Media',
            'descripcion' => 'Descripción de prueba',
            'funcionario_id' => $funcionario->id,
        ]);
    }

    public function test_rate_limiting_importar_cmdb()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('equipos.importar');

        $this->actingAs($user);

        $file = UploadedFile::fake()->create('test.xlsx', 100);

        // Peticion 1
        $response1 = $this->post('/equipos/importar', ['archivo' => $file]);
        // Peticion 2
        $response2 = $this->post('/equipos/importar', ['archivo' => $file]);
        // Peticion 3 (Debe ser bloqueada por throttle:2,1)
        $response3 = $this->post('/equipos/importar', ['archivo' => $file]);

        $this->assertEquals(429, $response3->status(), 'La tercera petición de importación en un minuto debe devolver 429 Too Many Requests.');
    }

    public function test_idor_tickets_destroy()
    {
        $userWithOnlyView = User::factory()->create();
        $userWithOnlyView->givePermissionTo('mesaayuda.ver');

        $ticket = $this->crearTicket();

        $response = $this->actingAs($userWithOnlyView)->delete("/tickets/{$ticket->id}");

        $response->assertStatus(403); // Forbidden
    }

    public function test_mass_assignment_complementos()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('equipos.crear');

        $equipo1 = $this->crearEquipo();
        $equipo2 = $this->crearEquipo();
        $catalogo = CatalogoComplemento::create(['nombre' => 'Mouse', 'descripcion' => 'Mouse USB']);

        // Intento de asignar complemento a equipo1, pero inyectando equipo_id de equipo2
        $response = $this->actingAs($user)->post("/equipos/{$equipo1->id}/complementos", [
            'catalogo_complemento_id' => $catalogo->id,
            'estado' => 'Asignado',
            'marca' => 'Test',
            'equipo_id' => $equipo2->id // Intento de inyección
        ]);

        $response->assertRedirect();

        // Verificamos que el complemento se guardó en equipo1 y NO en equipo2
        $this->assertTrue($equipo1->complementos()->count() > 0);
        $this->assertTrue($equipo2->complementos()->count() == 0);
    }

    public function test_file_upload_mimes()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('mesaayuda.editar');

        $ticket = $this->crearTicket();

        // Simular archivo malicioso PHP
        $file = UploadedFile::fake()->create('exploit.php', 100, 'application/x-httpd-php');

        $response = $this->actingAs($user)->post("/tickets/{$ticket->id}/evidencia", [
            'archivos' => [$file]
        ]);

        // Debe fallar la validación por mimetype
        $response->assertSessionHasErrors('archivos.0');
    }
}
