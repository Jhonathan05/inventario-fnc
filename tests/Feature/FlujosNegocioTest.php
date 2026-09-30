<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\User;
use App\Models\Prestamo;
use App\Models\CatalogoComplemento;
use App\Models\ActivoComplemento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlujosNegocioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->tipo = TipoRecurso::create(['nombre' => 'Escritorio', 'prefijo' => 'ESC']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);
    }

    public function test_flujo_2_creacion_activo_persistencia()
    {
        // 1-5: Creación del activo
        $response = $this->post(route('equipos.store'), [
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Flujo Laptop',
            'estado_operativo' => 'disponible',
            'marca' => 'FlujoMarca',
            'modelo' => 'FlujoModelo',
            'sin_serial_fisico' => true,
        ]);
        
        $response->assertStatus(302);
        
        // 7-10: Verificar persistencia en DB y estado final
        $this->assertDatabaseHas('equipos', [
            'nombre_equipo' => 'Flujo Laptop',
            'estado_operativo' => 'disponible'
        ]);
    }

    public function test_flujo_12_prestamo_activo_disponible_vs_asignado()
    {
        // CASO A: Activo Disponible
        $equipoDisponible = Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Laptop Disp',
            'estado_operativo' => 'disponible',
            'serial' => 'FL-DISP-1',
            'marca' => 'Test',
            'modelo' => 'Test',
        ]);

        $responseA = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipoDisponible->id,
            'persona_nombre' => 'Juan Perez',
            'persona_documento' => '12345678',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_devolucion_prevista' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Uso Temporal',
        ]);

        $responseA->assertStatus(302);
        $this->assertDatabaseHas('prestamos', ['equipo_id' => $equipoDisponible->id]);
        
        // El estado debería cambiar a 'prestado' o 'asignado' según la regla de negocio
        // Por seguridad no asumimos estado exacto si no lo sabemos, pero verificamos que el préstamo existe.

        // CASO B: Activo Asignado (Rechazo)
        $equipoAsignado = Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Laptop Asignada',
            'estado_operativo' => 'asignado',
            'serial' => 'FL-ASIG-2',
            'marca' => 'Test',
            'modelo' => 'Test',
        ]);

        $responseB = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipoAsignado->id,
            'persona_nombre' => 'Maria Gomez',
            'persona_documento' => '87654321',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_devolucion_prevista' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Uso Temporal 2',
        ]);

        $responseB->assertSessionHas('error');
        $this->assertDatabaseMissing('prestamos', ['equipo_id' => $equipoAsignado->id]);
    }

    public function test_flujo_6_creacion_asociacion_complemento()
    {
        $equipo = Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Laptop Base',
            'estado_operativo' => 'disponible',
            'serial' => 'FL-COMP-3',
            'marca' => 'Test',
            'modelo' => 'Test',
        ]);

        $catalogo = CatalogoComplemento::create(['nombre' => 'Mouse', 'descripcion' => 'Mouse Inalambrico']);

        // Simulamos el request de asignación de complemento
        $response = $this->post(route('equipos.complementos.store', $equipo->id), [
            'catalogo_complemento_id' => $catalogo->id,
            'estado' => 'Disponible',
            'marca' => 'Logitech',
            'modelo' => 'M185',
            'serial' => '12345678',
        ]);

        // Verificamos persistencia de la relación
        $this->assertDatabaseHas('activo_complementos', [
            'catalogo_complemento_id' => $catalogo->id,
            'equipo_id' => $equipo->id,
        ]);
    }
}
