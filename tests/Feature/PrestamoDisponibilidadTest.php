<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\User;
use App\Models\TipoRecurso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrestamoDisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        // Setup initial required data
        $this->tipo = TipoRecurso::create(['nombre' => 'Portátil', 'prefijo' => 'POR']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);
    }

    private function crearEquipo(string $estado): Equipo
    {
        return Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Test',
            'estado_operativo' => $estado,
            'serial' => 'TEST-SERIAL-' . rand(),
            'marca' => 'TestMarca',
            'modelo' => 'TestModelo',
        ]);
    }

    /**
     * Cuerpo válido de préstamo según PrestamoController::store.
     */
    private function payload(Equipo $equipo): array
    {
        return [
            'equipo_id' => $equipo->id,
            'persona_nombre' => 'Persona de Prueba',
            'persona_documento' => '79912904',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_devolucion_prevista' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ];
    }

    public function test_caso_1_disponible_permite_prestamo()
    {
        $equipo = $this->crearEquipo('disponible');

        $response = $this->post(route('prestamos.store'), $this->payload($equipo));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('prestamos', [
            'equipo_id' => $equipo->id,
            'estado' => 'Activo',
        ]);
    }

    public function test_caso_2_asignado_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('asignado');

        $response = $this->post(route('prestamos.store'), $this->payload($equipo));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('prestamos', ['equipo_id' => $equipo->id]);
    }

    public function test_caso_3_prestamo_activo_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('disponible');

        Prestamo::create([
            'equipo_id' => $equipo->id,
            'persona_nombre' => 'Persona Previa',
            'user_id' => $this->admin->id,
            'fecha_inicio' => now(),
            'fecha_devolucion_prevista' => now()->addDays(2),
            'motivo' => 'Test',
            'estado' => 'Activo',
        ]);

        $response = $this->post(route('prestamos.store'), $this->payload($equipo));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('prestamos', 1);
    }

    public function test_caso_4_reparacion_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('mantenimiento');

        $response = $this->post(route('prestamos.store'), $this->payload($equipo));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('prestamos', ['equipo_id' => $equipo->id]);
    }

    public function test_caso_6_baja_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('baja');

        $response = $this->post(route('prestamos.store'), $this->payload($equipo));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('prestamos', ['equipo_id' => $equipo->id]);
    }
}
