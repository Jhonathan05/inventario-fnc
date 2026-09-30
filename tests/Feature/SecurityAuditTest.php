<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Equipo;
use App\Models\ActivoComplemento;
use App\Models\CatalogoComplemento;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;

class SecurityAuditTest extends TestCase
{
    // NO usamos RefreshDatabase porque NO QUEREMOS BORRAR PRODUCCION SI ESTO CORRE AHI.
    // Usaremos base de datos de test si esta configurado, de lo contrario transacciones de base de datos.
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Asegurarse de limpiar rate limiters para la prueba
        RateLimiter::clear('equipos.importar');
        RateLimiter::clear('equipos.exportar');
        // Crear permisos básicos para las pruebas
        Permission::firstOrCreate(['name' => 'mesaayuda.ver']);
        Permission::firstOrCreate(['name' => 'mesaayuda.eliminar']);
        Permission::firstOrCreate(['name' => 'equipos.crear']);
        Permission::firstOrCreate(['name' => 'equipos.importar']);
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
        
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($userWithOnlyView)->delete("/tickets/{$ticket->id}");

        $response->assertStatus(403); // Forbidden
    }

    public function test_mass_assignment_complementos()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('equipos.crear');
        
        $equipo1 = Equipo::factory()->create();
        $equipo2 = Equipo::factory()->create();
        $catalogo = CatalogoComplemento::factory()->create();

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
        Permission::firstOrCreate(['name' => 'mesaayuda.editar']);
        $user->givePermissionTo('mesaayuda.editar');
        
        $ticket = Ticket::factory()->create();
        
        // Simular archivo malicioso PHP
        $file = UploadedFile::fake()->create('exploit.php', 100, 'application/x-httpd-php');

        $response = $this->actingAs($user)->post("/tickets/{$ticket->id}/evidencia", [
            'archivos' => [$file]
        ]);

        // Debe fallar la validación por mimetype
        $response->assertSessionHasErrors('archivos.0');
    }
}
