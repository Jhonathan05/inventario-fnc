<?php

namespace Tests\Feature;

use App\Models\Tarea;
use App\Models\TipoRecurso;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TareasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_admin_ve_el_tablero_de_tareas(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tareas.index'))
            ->assertOk()
            ->assertSee('Prioridad alta')
            ->assertSee('Derivadas CMDB')
            ->assertSee('Hechas recientemente');
    }

    public function test_usuario_sin_permiso_no_entra(): void
    {
        $sinPermiso = User::factory()->create();

        $this->actingAs($sinPermiso)
            ->get(route('tareas.index'))
            ->assertForbidden();
    }

    public function test_el_filtro_por_busqueda_acota_el_tablero(): void
    {
        Tarea::create([
            'titulo' => 'Auditar impresoras del tercer piso',
            'prioridad' => 'Alta',
            'modulo' => 'general',
            'estado' => Tarea::ESTADO_PENDIENTE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('tareas.index', ['q' => 'Auditar impresoras']))
            ->assertOk()
            ->assertSee('Auditar impresoras del tercer piso');

        $this->actingAs($this->admin)
            ->get(route('tareas.index', ['q' => 'no-existe-en-el-tablero']))
            ->assertOk()
            ->assertDontSee('Auditar impresoras del tercer piso');
    }

    public function test_el_filtro_de_origen_separa_manuales_de_derivadas(): void
    {
        $tipo = TipoRecurso::create(['nombre' => 'Portátil', 'prefijo' => 'POR']);

        Tarea::create([
            'titulo' => 'Revisar permisos de impresión',
            'prioridad' => 'Media',
            'modulo' => 'general',
            'estado' => Tarea::ESTADO_PENDIENTE,
            'created_by' => $this->admin->id,
        ]);

        \App\Models\Equipo::create([
            'tipo_recurso_id' => $tipo->id,
            'nombre_equipo' => 'PENDIENTE',
            'serial' => 'SN-' . uniqid(),
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'estado_operativo' => 'disponible',
        ]);

        $this->actingAs($this->admin)
            ->get(route('tareas.index', ['origen' => 'manuales']))
            ->assertOk()
            ->assertSee('Revisar permisos de impresión')
            ->assertDontSee('Actualizar datos del activo');

        $this->actingAs($this->admin)
            ->get(route('tareas.index', ['origen' => 'derivadas']))
            ->assertOk()
            ->assertSee('Actualizar datos del activo')
            ->assertDontSee('Revisar permisos de impresión');
    }

    public function test_tarea_completada_aparece_en_hechas_recientes(): void
    {
        $tarea = Tarea::create([
            'titulo' => 'Depurar seriales duplicados',
            'prioridad' => 'Baja',
            'modulo' => 'equipos',
            'estado' => Tarea::ESTADO_PENDIENTE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('tareas.toggle', $tarea))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('tareas.index'))
            ->assertOk()
            ->assertSee('Depurar seriales duplicados')
            ->assertSee('Reabrir');
    }
}
