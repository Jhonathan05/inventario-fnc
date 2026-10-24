<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Asignacion;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\HistorialAdministrativo;
use App\Models\TipoRecurso;
use App\Models\User;
use App\Models\UsuarioAsignado;
use App\Services\ActaService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote A — "Custodia única y acta que sí asigna" (plan §11).
 *
 *  1. El acta de asignación SÍ deja custodia visible (usuario_asignados +
 *     snapshot responsable_* + ítem vigente + historial administrativo).
 *  2. El retiro cierra la custodia.
 *  3. La regla "un computador" consulta la custodia ACTUAL, no el historial.
 *  4. La anulación funciona en actas multi-ítem y revierte estado + custodia.
 */
class ActaCustodiaTest extends TestCase
{
    use RefreshDatabase;

    private ActaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);

        $this->tipo = TipoRecurso::create(['nombre' => 'Equipo Portátil', 'prefijo' => 'POR', 'es_computador' => true]);
        $this->service = app(ActaService::class);
    }

    private function equipoDisponible(string $serial): Equipo
    {
        return Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'serial'          => $serial,
            'nombre_equipo'   => 'PC-' . $serial,
            'marca'           => 'Lenovo',
            'modelo'          => 'T14',
            'estado_operativo'=> 'disponible',
        ]);
    }

    private function datosActa(string $cedula, string $nombre = 'María Pérez'): array
    {
        return [
            'tipo_accion'  => Acta::TIPO_INVENTARIO_FISICO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'usuario_cedula' => $cedula,
            'usuario_nombre' => $nombre,
            'usuario_cargo'  => 'Analista',
            'usuario_area'   => 'TIC',
            'usuario_ciudad' => 'Bogotá',
        ];
    }

    /* ── 1. Asignar deja custodia visible ─────────────────────────────── */

    public function test_el_acta_de_asignacion_abre_la_custodia(): void
    {
        $equipo = $this->equipoDisponible('SN-001');
        $acta = $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        $equipo = $equipo->fresh();

        // Proyección de custodia actual
        $ua = $equipo->usuarioAsignado;
        $this->assertNotNull($ua, 'La ficha debe mostrar el custodio tras el acta.');
        $this->assertSame('1075310355', $ua->cedula);
        $this->assertSame('María Pérez', $ua->nombre);
        $this->assertSame('asignado', $ua->modalidad);
        $this->assertEquals($acta->id, $ua->acta_id);
        $this->assertNotNull($ua->desde);

        // Snapshot del equipo (lo que ven listados y reportes)
        $this->assertSame('1075310355', $equipo->responsable_cedula);
        $this->assertSame('María Pérez', $equipo->responsable_nombre);

        // Ítem del acta como custodia vigente
        $item = Asignacion::where('acta_id', $acta->id)->where('equipo_id', $equipo->id)->first();
        $this->assertTrue((bool) $item->vigente);
        $this->assertSame('disponible', $item->estado_anterior);
        $this->assertSame('en_operacion', $item->estado_resultante);

        // Historial administrativo del movimiento
        $this->assertDatabaseHas('historial_administrativos', [
            'equipo_id' => $equipo->id,
            'tipo_cambio' => 'acta_inventario_fisico',
        ]);
    }

    public function test_el_retiro_cierra_la_custodia(): void
    {
        $equipo = $this->equipoDisponible('SN-002');
        $this->service->registrar($this->datosActa('1075310355'), [$equipo]);
        $this->assertNotNull($equipo->fresh()->usuarioAsignado);

        $retiro = $this->service->registrar([
            'tipo_accion'  => Acta::TIPO_RETIRO_DEFINITIVO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'destino'      => 'baja',
            'usuario_cedula' => '1075310355',
            'usuario_nombre' => 'María Pérez',
        ], [$equipo->fresh()]);

        $equipo = $equipo->fresh();
        $this->assertSame('baja', $equipo->estado_operativo);
        $this->assertNull($equipo->usuarioAsignado, 'El retiro debe liberar la custodia.');
        $this->assertNull($equipo->responsable_cedula);

        // El ítem de la asignación original quedó cerrado (historial intacto)
        $itemAsignacion = Asignacion::where('equipo_id', $equipo->id)
            ->where('tipo_accion', Acta::TIPO_INVENTARIO_FISICO)->first();
        $this->assertFalse((bool) $itemAsignacion->vigente);
        $this->assertNotNull($itemAsignacion->fecha_fin);
    }

    /* ── 2. Regla "un computador" con custodia ACTUAL ─────────────────── */

    public function test_la_regla_del_computador_usa_custodia_actual_no_historial(): void
    {
        // María tiene un computador…
        $anterior = $this->equipoDisponible('SN-003');
        $this->service->registrar($this->datosActa('1075310355'), [$anterior]);

        // …que luego es retirado (pasa a baja) y reasignado a otra persona.
        $this->service->registrar([
            'tipo_accion' => Acta::TIPO_RETIRO_DEFINITIVO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'destino' => 'disponible',
            'usuario_cedula' => '1075310355', 'usuario_nombre' => 'María Pérez',
        ], [$anterior->fresh()]);
        $this->service->registrar([
            'tipo_accion' => Acta::TIPO_INVENTARIO_FISICO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'usuario_cedula' => '1000001', 'usuario_nombre' => 'Pedro Gómez',
        ], [$anterior->fresh()]);

        // María ya NO tiene computador (la custodia actual es de Pedro):
        // una nueva asignación a María debe pasar sin autorización.
        $nuevo = $this->equipoDisponible('SN-004');
        $acta = $this->service->registrar($this->datosActa('1075310355'), [$nuevo]);

        $this->assertInstanceOf(Acta::class, $acta);
        $this->assertSame('1075310355', $nuevo->fresh()->usuarioAsignado->cedula);
    }

    public function test_segundo_computador_exige_autorizacion(): void
    {
        $primero = $this->equipoDisponible('SN-005');
        $this->service->registrar($this->datosActa('1075310355'), [$primero]);

        $segundo = $this->equipoDisponible('SN-006');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('autorización');
        $this->service->registrar($this->datosActa('1075310355'), [$segundo]);
    }

    public function test_segundo_computador_con_autorizacion_pasa(): void
    {
        $primero = $this->equipoDisponible('SN-007');
        $this->service->registrar($this->datosActa('1075310355'), [$primero]);

        $segundo = $this->equipoDisponible('SN-008');
        $datos = $this->datosActa('1075310355');
        $datos['autorizacion_path'] = 'autorizaciones/jefe.pdf';

        $acta = $this->service->registrar($datos, [$segundo]);
        $this->assertSame('1075310355', $segundo->fresh()->usuarioAsignado->cedula);
    }

    /* ── 3. Anulación multi-ítem + reversión de custodia ──────────────── */

    public function test_se_puede_anular_un_acta_multi_item(): void
    {
        $a = $this->equipoDisponible('SN-009');
        $b = $this->equipoDisponible('SN-010');
        $acta = $this->service->registrar($this->datosActa('1075310355'), [$a, $b]);

        // Antes del Lote A esto fallaba: los ítems propios contaban como
        // "movimientos posteriores".
        $this->service->anular($acta, 'Registro erróneo', $this->admin);

        $this->assertTrue($acta->fresh()->anulada());
        $this->assertSame('disponible', $a->fresh()->estado_operativo);
        $this->assertSame('disponible', $b->fresh()->estado_operativo);
    }

    public function test_anular_revierte_estado_y_limpia_custodia_cuando_no_habia_previa(): void
    {
        $equipo = $this->equipoDisponible('SN-011');
        $acta = $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        $this->service->anular($acta, 'Error de cédula', $this->admin);

        $equipo = $equipo->fresh();
        $this->assertSame('disponible', $equipo->estado_operativo);
        $this->assertNull($equipo->usuarioAsignado);
        $this->assertNull($equipo->responsable_cedula);
    }

    public function test_anular_un_cambio_de_responsable_restaura_al_custodio_anterior(): void
    {
        $equipo = $this->equipoDisponible('SN-012');

        // Custodia original: María.
        $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        // Cambio de responsable a Pedro.
        $actaCambio = $this->service->registrar([
            'tipo_accion' => Acta::TIPO_CAMBIO_RESPONSABLE,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'usuario_cedula' => '1000001', 'usuario_nombre' => 'Pedro Gómez',
        ], [$equipo->fresh()]);

        $this->assertSame('1000001', $equipo->fresh()->usuarioAsignado->cedula);

        // Se anula el cambio: la custodia vuelve a María (desde el historial).
        $this->service->anular($actaCambio, 'Cambio indebido', $this->admin);

        $ua = $equipo->fresh()->usuarioAsignado;
        $this->assertNotNull($ua);
        $this->assertSame('1075310355', $ua->cedula, 'La anulación debe restaurar al custodio anterior.');
        $this->assertSame('1075310355', $equipo->fresh()->responsable_cedula);
    }

    public function test_anular_un_retiro_reabre_la_custodia_previa(): void
    {
        $equipo = $this->equipoDisponible('SN-013');
        $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        $retiro = $this->service->registrar([
            'tipo_accion' => Acta::TIPO_RETIRO_DEFINITIVO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'destino' => 'baja',
            'usuario_cedula' => '1075310355', 'usuario_nombre' => 'María Pérez',
        ], [$equipo->fresh()]);

        $this->assertNull($equipo->fresh()->usuarioAsignado);

        $this->service->anular($retiro, 'Retiro equivocado', $this->admin);

        $equipo = $equipo->fresh();
        $this->assertSame('en_operacion', $equipo->estado_operativo, 'El estado vuelve al anterior al retiro.');
        $ua = $equipo->usuarioAsignado;
        $this->assertNotNull($ua, 'Anular el retiro debe reabrir la custodia previa.');
        $this->assertSame('1075310355', $ua->cedula);
    }

    public function test_no_se_puede_anular_un_acta_con_movimientos_posteriores(): void
    {
        $equipo = $this->equipoDisponible('SN-014');
        $acta = $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        // Movimiento posterior real (otra acta distinta).
        $this->service->registrar([
            'tipo_accion' => Acta::TIPO_RETIRO_DEFINITIVO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'destino' => 'disponible',
            'usuario_cedula' => '1075310355', 'usuario_nombre' => 'María Pérez',
        ], [$equipo->fresh()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('posteriores');
        $this->service->anular($acta, 'Tarde', $this->admin);
    }

    /* ── 4. Cambio de responsable = custodia nueva, una sola fila ─────── */

    public function test_cambio_de_responsable_deja_una_sola_custodia_y_cierra_el_item_previo(): void
    {
        $equipo = $this->equipoDisponible('SN-015');
        $this->service->registrar($this->datosActa('1075310355'), [$equipo]);

        $this->service->registrar([
            'tipo_accion' => Acta::TIPO_CAMBIO_RESPONSABLE,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'usuario_cedula' => '1000001', 'usuario_nombre' => 'Pedro Gómez',
        ], [$equipo->fresh()]);

        $equipo = $equipo->fresh();

        // Proyección 1:1 — solo una fila, la del nuevo custodio.
        $this->assertSame(1, UsuarioAsignado::where('equipo_id', $equipo->id)->count());
        $this->assertSame('1000001', $equipo->usuarioAsignado->cedula);

        // El ítem original quedó cerrado y el nuevo quedó vigente.
        $items = Asignacion::where('equipo_id', $equipo->id)->orderBy('id')->get();
        $this->assertFalse((bool) $items[0]->vigente);
        $this->assertNotNull($items[0]->fecha_fin);
        $this->assertTrue((bool) $items[1]->vigente);
    }

    public function test_el_prestamo_por_acta_deja_custodia_del_prestatario(): void
    {
        $equipo = $this->equipoDisponible('SN-016');
        $acta = $this->service->registrar([
            'tipo_accion'  => Acta::TIPO_PRESTAMO,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin_probable' => now()->addDays(15)->toDateString(),
            'usuario_cedula' => '1000002', 'usuario_nombre' => 'Contratista X',
        ], [$equipo]);

        $equipo = $equipo->fresh();
        $this->assertSame('prestamo', $equipo->estado_operativo);
        $ua = $equipo->usuarioAsignado;
        $this->assertNotNull($ua);
        $this->assertSame('1000002', $ua->cedula);
        $this->assertEquals($acta->id, $ua->acta_id);
    }
}
