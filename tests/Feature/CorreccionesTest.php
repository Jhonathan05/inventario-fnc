<?php

namespace Tests\Feature;

use App\Models\Correccion;
use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\User;
use App\Services\CorreccionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\CatalogosCmdbSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pestaña "Correcciones" — importación no condicionada.
 *
 * Reglas cubiertas:
 *  - La importación de filas incompletas NO se bloquea.
 *  - Cada faltante (vacío, placeholder, fuera de catálogo, custodia) queda
 *    registrado como corrección pendiente.
 *  - Cada pendiente es corregible directamente (o justificable).
 *  - El escaneo es idempotente y auto-resuelve lo que ya pasó.
 */
class CorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private CorreccionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, CatalogosCmdbSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);

        $this->tipo = TipoRecurso::create(['nombre' => 'EQUIPO PORTATIL', 'prefijo' => 'POR']);
        $this->service = app(CorreccionService::class);
    }

    private function equipoIncompleto(): Equipo
    {
        return Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'serial'          => 'SERIAL123',
            'placa'           => 'F100001',
            'nombre_equipo'   => 'PENDIENTE',
            'marca'           => 'Lenovo',
            'modelo'          => 'ThinkPad',
            'estado_operativo'=> 'en_operacion',
            'razon_estado'    => 'VALOR INVENTADO',
            'estado_uso'      => 'FUNCIONES PROPIAS',
            'administrado'    => 'CONTROLADO',
        ]);
    }

    public function test_detecta_todos_los_faltantes_de_un_equipo_incompleto(): void
    {
        $equipo = $this->equipoIncompleto();
        // 'razon_estado' fuera de catálogo + 'nombre_equipo' PENDIENTE +
        // EN OPERACIÓN sin custodio + el resto de ficha vacía.
        $nuevas = $this->service->generarParaEquipo($equipo);

        $this->assertGreaterThan(0, $nuevas);

        $campos = Correccion::where('equipo_id', $equipo->id)->pluck('campo');
        $this->assertTrue($campos->contains('nombre_equipo'), 'Debe detectar el placeholder PENDIENTE.');
        $this->assertTrue($campos->contains('razon_estado'), 'Debe detectar el valor fuera de catálogo.');
        $this->assertTrue($campos->contains('custodia.cedula'), 'Debe detectar EN OPERACIÓN sin custodio.');
        $this->assertTrue($campos->contains('custodia.nombre'));
        $this->assertTrue($campos->contains('fin_garantia'), 'Debe detectar la garantía vacía.');
        $this->assertTrue($campos->contains('checklist_responsable'));

        $alta = Correccion::where('equipo_id', $equipo->id)->where('severidad', 'alta')->count();
        $this->assertGreaterThanOrEqual(3, $alta);
    }

    public function test_el_escaneo_es_idempotente(): void
    {
        $equipo = $this->equipoIncompleto();
        $primeras = $this->service->generarParaEquipo($equipo);
        $segundas = $this->service->generarParaEquipo($equipo);

        $this->assertSame(0, $segundas, 'La segunda pasada no debe duplicar pendientes.');
        $this->assertSame($primeras, (int) Correccion::where('equipo_id', $equipo->id)->where('estado', 'pendiente')->count());
    }

    public function test_resolver_corrige_el_dato_y_marca_resuelta(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'nombre_equipo')->where('estado', 'pendiente')->first();

        $this->service->resolver($pendiente, 'W11FETOLNGUZ01', $this->admin);

        $this->assertSame('resuelta', $pendiente->fresh()->estado);
        $this->assertSame('W11FETOLNGUZ01', $equipo->fresh()->nombre_equipo);
    }

    public function test_resolver_con_catalogo_valida_el_valor(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'razon_estado')->where('estado', 'pendiente')->first();
        $this->assertNotNull($pendiente, 'El valor fuera de catálogo debe quedar pendiente.');

        $this->service->resolver($pendiente, 'ASIGNACIÓN RESPONSABLE', $this->admin);
        $this->assertSame('ASIGNACIÓN RESPONSABLE', $equipo->fresh()->razon_estado);

        $this->expectException(\InvalidArgumentException::class);
        $fuera = Correccion::where('equipo_id', $equipo->id)->where('campo', 'estado_uso')->where('estado', 'pendiente')->first();
        if ($fuera) {
            $this->service->resolver($fuera, 'VALOR QUE NO EXISTE', $this->admin);
        } else {
            $this->fail('estado_uso debía estar pendiente.');
        }
    }

    public function test_resolver_custodia_crea_el_usuario_asignado(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $cedula = Correccion::where('equipo_id', $equipo->id)->where('campo', 'custodia.cedula')->first();
        $nombre = Correccion::where('equipo_id', $equipo->id)->where('campo', 'custodia.nombre')->first();

        $this->service->resolver($cedula, '1075310355', $this->admin);
        $this->service->resolver($nombre, 'Natalia Guzmán', $this->admin);

        $ua = $equipo->fresh()->usuarioAsignado;
        $this->assertNotNull($ua);
        $this->assertSame('1075310355', $ua->cedula);
        $this->assertSame('Natalia Guzmán', $ua->nombre);
    }

    public function test_resolver_fecha_parsea_dd_mm_aaaa(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'fin_garantia')->where('estado', 'pendiente')->first();

        $this->service->resolver($pendiente, '31/12/2027', $this->admin);
        $this->assertSame('2027-12-31', $equipo->fresh()->fin_garantia->toDateString());
    }

    public function test_auto_resuelve_pendientes_que_ya_pasaron(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        // Se corrige por fuera (simula una edición manual del activo)…
        $equipo->update(['nombre_equipo' => 'W11PC-001']);

        // …y un re-escaneo archiva el pendiente de ese campo.
        $this->service->generarParaEquipo($equipo->fresh(), 'escaneo');

        $this->assertSame('resuelta', Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'nombre_equipo')->first()->estado);
    }

    public function test_ignorar_exige_justificacion(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'orden_remision')->where('estado', 'pendiente')->first();
        $this->assertNotNull($pendiente);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->ignorar($pendiente, 'x', $this->admin);

        $this->service->ignorar($pendiente, 'El proveedor no entrega orden de remisión.', $this->admin);
        $this->assertSame('ignorada', $pendiente->fresh()->estado);
    }

    public function test_endpoint_de_opciones_devuelve_el_catalogo_oficial(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'razon_estado')->where('estado', 'pendiente')->first();

        $response = $this->getJson(route('correcciones.opciones', $pendiente));
        $response->assertOk();
        $valores = collect($response->json('opciones'))->pluck('valor');
        $this->assertContains('ASIGNACIÓN RESPONSABLE', $valores);
        $this->assertCount(13, $valores);
    }

    public function test_la_vista_de_correcciones_lista_y_filtra(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $this->get(route('correcciones.index'))
            ->assertOk()
            ->assertSee('Correcciones')
            ->assertSee($equipo->placa);

        $this->get(route('correcciones.index', ['seccion' => 'custodia']))
            ->assertOk()
            ->assertSee('Cédula del custodio');
    }

    public function test_resolver_por_http_aplica_y_redirige(): void
    {
        $equipo = $this->equipoIncompleto();
        $this->service->generarParaEquipo($equipo);

        $pendiente = Correccion::where('equipo_id', $equipo->id)
            ->where('campo', 'nombre_equipo')->where('estado', 'pendiente')->first();

        $this->post(route('correcciones.resolver', $pendiente), ['valor' => 'W11FETOLNGUZ01'])
            ->assertRedirect();

        $this->assertSame('W11FETOLNGUZ01', $equipo->fresh()->nombre_equipo);
        $this->assertSame('resuelta', $pendiente->fresh()->estado);
    }

    public function test_regenerar_escanea_todos_los_activos(): void
    {
        $this->equipoIncompleto();
        $nuevos = $this->service->regenerarTodo('escaneo');

        $this->assertGreaterThan(0, $nuevos);
        $this->assertGreaterThan(0, Correccion::where('estado', 'pendiente')->count());
    }
}
