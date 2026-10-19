<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Licencia;
use App\Models\Prestamo;
use App\Models\TipoRecurso;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TipoRecurso $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tipo = TipoRecurso::create(['nombre' => 'Portátil', 'prefijo' => 'POR']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function crearEquipo(array $atributos = []): Equipo
    {
        return Equipo::create(array_merge([
            'tipo_recurso_id' => $this->tipo->id,
            'nombre_equipo' => 'Equipo de prueba ' . uniqid(),
            'serial' => 'SN-' . uniqid(),
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'estado_operativo' => 'disponible',
        ], $atributos));
    }

    public function test_el_administrador_ve_el_panel_de_control(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Panel de control');
        $response->assertSee('Activos registrados');
    }

    public function test_un_usuario_sin_permiso_no_accede_al_panel(): void
    {
        $sinRol = User::factory()->create();

        $this->actingAs($sinRol)->get(route('dashboard'))->assertForbidden();
    }

    public function test_los_indicadores_reflejan_el_inventario_real(): void
    {
        $this->crearEquipo(['estado_operativo' => 'disponible']);
        $this->crearEquipo(['estado_operativo' => 'disponible']);
        $this->crearEquipo(['estado_operativo' => 'en_operacion']);
        $this->crearEquipo(['estado_operativo' => 'reparacion']);
        $this->crearEquipo(['estado_operativo' => 'baja']);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('inventario', static function (array $inventario): bool {
            return $inventario['total'] === 5
                && $inventario['asignados'] === 1
                && $inventario['disponibles'] === 2
                && $inventario['reparacion'] === 1
                && $inventario['baja'] === 1;
        });
    }

    public function test_la_ventana_de_vencimientos_es_configurable_y_se_valida(): void
    {
        $valida = $this->actingAs($this->admin)->get(route('dashboard', ['ventana' => 90]));
        $valida->assertOk();
        $valida->assertViewHas('ventana', 90);

        $invalida = $this->actingAs($this->admin)->get(route('dashboard', ['ventana' => 7]));
        $invalida->assertOk();
        $invalida->assertViewHas('ventana', 30);
    }

    public function test_las_alertas_incluyen_licencias_vencidas_y_prestamos_atrasados(): void
    {
        Licencia::create([
            'nombre' => 'Antivirus Endpoint',
            'tipo_licencia' => 'Suscripción',
            'cantidad_maxima' => 50,
            'estado' => 'Vencida',
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
        ]);

        $equipo = $this->crearEquipo();

        Prestamo::create([
            'equipo_id' => $equipo->id,
            'persona_nombre' => 'Ana Pérez',
            'user_id' => $this->admin->id,
            'fecha_inicio' => now()->subDays(10),
            'fecha_devolucion_prevista' => now()->subDays(2),
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('alertas', static function (array $alertas): bool {
            $claves = array_column($alertas, 'key');

            return in_array('licencias_vencidas', $claves, true)
                && in_array('prestamos_vencidos', $claves, true);
        });
        $response->assertSee('Licencias vencidas');
        $response->assertSee('Antivirus Endpoint');
    }

    public function test_el_analista_ve_los_mismos_bloques_que_el_administrador(): void
    {
        $analista = User::factory()->create();
        $analista->assignRole('Analista Tic');

        $response = $this->actingAs($analista)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('puede', static function (array $puede): bool {
            return $puede['licencias'] === true
                && $puede['tickets'] === true
                && $puede['equipos'] === true;
        });
        $response->assertSee('Sin cupos parametrizados');
        $response->assertSee('Tickets de soporte');
    }
}
