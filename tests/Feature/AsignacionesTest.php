<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->actingAs($admin);
    }

    /**
     * Regresión: `AsignacionController` no podía instanciarse porque inyectaba
     * `PdfService` sin importarlo (se resolvía a `App\Http\Controllers\PdfService`),
     * de modo que TODAS las rutas `/asignaciones/*` respondían 500.
     */
    public function test_el_listado_de_asignaciones_carga(): void
    {
        $this->get(route('asignaciones.index'))
            ->assertOk()
            ->assertViewIs('asignaciones.index');
    }

    public function test_los_funcionarios_elegibles_responden_json(): void
    {
        $this->getJson(route('asignaciones.funcionarios-elegibles'))
            ->assertOk();
    }
}
