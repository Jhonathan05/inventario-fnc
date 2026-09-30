<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\ActivoComplemento;
use App\Models\CatalogoComplemento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplementosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->catalogo = CatalogoComplemento::create(['nombre' => 'Teclado', 'descripcion' => 'Teclado USB']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);
    }

    public function test_puede_listar_complementos_globales()
    {
        $response = $this->get(route('equipos.complementos.global'));
        
        $response->assertStatus(200);
    }
}
