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

    private function crearEquipo($estado)
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

    public function test_caso_1_disponible_permite_prestamo()
    {
        $equipo = $this->crearEquipo('disponible');
        
        $response = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ]);
        
        $response->assertStatus(302);
        $this->assertDatabaseHas('prestamos', ['equipo_id' => $equipo->id]);
    }

    public function test_caso_2_asignado_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('asignado');
        
        $response = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ]);
        
        $response->assertSessionHasErrors(['equipo_id']);
    }

    public function test_caso_3_prestamo_activo_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('disponible');
        
        Prestamo::create([
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
            'estado' => 'Activo'
        ]);
        
        $response = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ]);
        
        $response->assertSessionHasErrors(['equipo_id']);
    }

    public function test_caso_4_reparacion_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('mantenimiento');
        
        $response = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ]);
        
        $response->assertSessionHasErrors(['equipo_id']);
    }

    public function test_caso_6_baja_rechaza_prestamo()
    {
        $equipo = $this->crearEquipo('baja');
        
        $response = $this->post(route('prestamos.store'), [
            'equipo_id' => $equipo->id,
            'usuario_id' => $this->admin->id,
            'fecha_prestamo' => now()->format('Y-m-d'),
            'fecha_devolucion' => now()->addDays(2)->format('Y-m-d'),
            'motivo' => 'Test',
        ]);
        
        $response->assertSessionHasErrors(['equipo_id']);
    }
}
