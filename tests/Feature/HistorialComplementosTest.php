<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\CatalogoComplemento;
use App\Models\ActivoComplemento;
use App\Models\HistorialComplemento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialComplementosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_admin_puede_ver_historial_global_de_complementos()
    {
        $this->actingAs($this->admin);

        // Crear datos mínimos
        $catalogo = CatalogoComplemento::create(['nombre' => 'Teclado', 'descripcion' => 'Teclado USB']);
        $equipo = Equipo::create([
            'tipo_recurso_id' => TipoRecurso::create(['nombre' => 'PC', 'prefijo' => 'PC'])->id,
            'nombre_equipo' => 'PC Test',
            'estado_operativo' => 'disponible',
            'serial' => 'PC-123',
            'marca' => 'Test',
            'modelo' => 'Test',
        ]);

        $complemento = ActivoComplemento::create([
            'catalogo_complemento_id' => $catalogo->id,
            'equipo_id' => $equipo->id,
            'estado' => 'Disponible',
            'serial' => 'KBD-123',
        ]);

        HistorialComplemento::create([
            'complemento_id' => $complemento->id,
            'usuario_id' => $this->admin->id,
            'evento' => 'Asignación',
            'observacion' => 'Asignado a PC Test',
            'activo_destino_id' => $equipo->id,
            'fecha' => now(),
        ]);

        $response = $this->get(route('equipos.complementos.historial_global'));

        $response->assertStatus(200);
        $response->assertSee('Asignado a PC Test');
    }

    public function test_admin_puede_ver_historial_individual_de_un_complemento()
    {
        $this->actingAs($this->admin);

        // Crear datos mínimos
        $catalogo = CatalogoComplemento::create(['nombre' => 'Monitor', 'descripcion' => 'Monitor 24']);
        
        $complemento = ActivoComplemento::create([
            'catalogo_complemento_id' => $catalogo->id,
            'estado' => 'Disponible',
            'serial' => 'MON-123',
        ]);

        HistorialComplemento::create([
            'complemento_id' => $complemento->id,
            'usuario_id' => $this->admin->id,
            'evento' => 'Creación',
            'observacion' => 'Creado inicial',
            'fecha' => now(),
        ]);

        $response = $this->get(route('equipos.complementos.historial_individual', $complemento->id));

        $response->assertStatus(200);
        $response->assertSee('Creado inicial');
    }
}
