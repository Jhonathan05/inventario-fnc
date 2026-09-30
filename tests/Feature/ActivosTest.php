<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->tipo = TipoRecurso::create(['nombre' => 'Portátil', 'prefijo' => 'POR']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        
        $this->actingAs($this->admin);
    }

    public function test_puede_crear_activo()
    {
        $response = $this->post(route('equipos.store'), [
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Laptop de Prueba',
            'estado_operativo' => 'disponible',
            'marca' => 'Dell',
            'modelo' => 'XPS',
            'sin_serial_fisico' => true,
        ]);
        
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('equipos', ['nombre_equipo' => 'Laptop de Prueba']);
    }

    public function test_no_puede_crear_activo_sin_tipo()
    {
        $response = $this->post(route('equipos.store'), [
            'nombre_equipo' => 'Laptop de Prueba',
        ]);
        
        $response->assertSessionHasErrors(['tipo_recurso_id']);
    }
}
