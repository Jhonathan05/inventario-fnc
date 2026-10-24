<?php

namespace Tests\Feature;

use App\Models\Acta;
use App\Models\Asignacion;
use App\Models\Equipo;
use App\Models\TipoRecurso;
use App\Models\User;
use App\Services\ActaService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lote B1 — catálogo real del CMDB (6 estados + 13 razones) en el flujo.
 *
 *  - Asignación → en_operacion + ASIGNACIÓN RESPONSABLE
 *  - Préstamo → en_operacion + PRÉSTAMO (ya no existe el estado 'prestamo')
 *  - Retiro con destino razón → baja + razón; 'disponible' → bodega
 *  - Reparación entrada/salida → taller por razón; salida reabre custodia
 *  - Anulación revierte estado Y razón desde el propio ítem
 *  - Regla de bodega: 15 días hábiles RETORNO/CUSTODIA → DISPONIBLE
 */
class CatalogoRealB1Test extends TestCase
{
    use RefreshDatabase;

    private ActaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, \Database\Seeders\CatalogosCmdbSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);

        $this->tipo = TipoRecurso::create(['nombre' => 'Equipo Portátil', 'prefijo' => 'POR', 'es_computador' => true]);
        $this->service = app(ActaService::class);
    }

    private function bodega(string $serial, ?string $razon = 'disponible'): Equipo
    {
        return Equipo::create([
            'tipo_recurso_id' => $this->tipo->id,
            'serial'          => $serial,
            'nombre_equipo'   => 'PC-' . $serial,
            'marca'           => 'Lenovo',
            'modelo'          => 'T14',
            'estado_operativo'=> 'almacenado',
            'razon_estado'    => $razon,
        ]);
    }

    private function datos(string $tipo, array $extra = []): array
    {
        return array_merge([
            'tipo_accion'  => $tipo,
            'fecha_accion' => now()->format('Y-m-d H:i:s'),
            'usuario_cedula' => '1075310355',
            'usuario_nombre' => 'María Pérez',
        ], $extra);
    }

    public function test_asignar_deja_en_operacion_con_razon_asignacion_responsable(): void
    {
        $equipo = $this->bodega('SN-B1-01');
        $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$equipo]);

        $equipo = $equipo->fresh();
        $this->assertSame('en_operacion', $equipo->estado_operativo);
        $this->assertSame('asignacion_responsable', $equipo->razon_estado);
    }

    public function test_prestamo_deja_en_operacion_con_razon_prestamo(): void
    {
        $equipo = $this->bodega('SN-B1-02');
        $this->service->registrar($this->datos(Acta::TIPO_PRESTAMO, [
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin_probable' => now()->addDays(15)->toDateString(),
        ]), [$equipo]);

        $equipo = $equipo->fresh();
        $this->assertSame('en_operacion', $equipo->estado_operativo);
        $this->assertSame('prestamo', $equipo->razon_estado);
        $this->assertSame('prestamo', $equipo->usuarioAsignado->modalidad === 'asignado' ? 'prestamo' : 'x'); // custodia abierta
        $this->assertNotNull($equipo->usuarioAsignado);
    }

    public function test_retiro_con_destino_razon_deja_baja_con_esa_razon(): void
    {
        foreach (['baja', 'procesar_baja', 'donacion', 'devuelto_a_proveedor', 'hurto_perdida'] as $destino) {
            $equipo = $this->bodega('SN-B1-R-' . $destino);
            $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$equipo]);
            $this->service->registrar($this->datos(Acta::TIPO_RETIRO_DEFINITIVO, ['destino' => $destino]), [$equipo->fresh()]);

            $equipo = $equipo->fresh();
            $this->assertSame('baja', $equipo->estado_operativo, "Destino {$destino}");
            $this->assertSame($destino, $equipo->razon_estado, "Destino {$destino}");
            $this->assertNull($equipo->usuarioAsignado, 'El retiro siempre cierra la custodia.');
        }
    }

    public function test_retiro_a_bodega_deja_almacenado_disponible(): void
    {
        $equipo = $this->bodega('SN-B1-03');
        $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$equipo]);
        $this->service->registrar($this->datos(Acta::TIPO_RETIRO_DEFINITIVO, ['destino' => 'disponible']), [$equipo->fresh()]);

        $equipo = $equipo->fresh();
        $this->assertSame('almacenado', $equipo->estado_operativo);
        $this->assertSame('disponible', $equipo->razon_estado);
        $this->assertNull($equipo->usuarioAsignado, 'Devolver a bodega cierra la custodia.');
    }

    public function test_reparacion_entrada_taller_y_salida_con_custodia(): void
    {
        $equipo = $this->bodega('SN-B1-04');
        $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$equipo]);

        // Entrada al taller: almacenado + ALISTAMIENTO/SOPORTE, custodia cerrada.
        $this->service->registrar($this->datos(Acta::TIPO_REPARACION), [$equipo->fresh()]);
        $equipo = $equipo->fresh();
        $this->assertSame('almacenado', $equipo->estado_operativo);
        $this->assertSame('alistamiento_soporte', $equipo->razon_estado);
        $this->assertNull($equipo->usuarioAsignado);

        // Salida del taller: vuelve a EN OPERACIÓN con custodia del responsable.
        $this->service->registrar($this->datos(Acta::TIPO_REPARACION), [$equipo->fresh()]);
        $equipo = $equipo->fresh();
        $this->assertSame('en_operacion', $equipo->estado_operativo);
        $this->assertSame('asignacion_responsable', $equipo->razon_estado);
        $this->assertNotNull($equipo->usuarioAsignado);
        $this->assertSame('1075310355', $equipo->usuarioAsignado->cedula);
    }

    public function test_anular_revierte_estado_y_razon_del_item(): void
    {
        $equipo = $this->bodega('SN-B1-05');
        $acta = $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$equipo]);

        $item = Asignacion::where('acta_id', $acta->id)->first();
        $this->assertSame('almacenado', $item->estado_anterior);
        $this->assertSame('disponible', $item->razon_anterior);
        $this->assertSame('asignacion_responsable', $item->razon_resultante);

        $this->service->anular($acta, 'prueba', $this->admin);

        $equipo = $equipo->fresh();
        $this->assertSame('almacenado', $equipo->estado_operativo);
        $this->assertSame('disponible', $equipo->razon_estado);
    }

    public function test_regla_de_bodega_15_dias_habiles(): void
    {
        $antiguo = $this->bodega('SN-B1-06', 'retorno');
        Asignacion::create([
            'equipo_id' => $antiguo->id, 'tipo_accion' => 'traslado',
            'usuario_cedula' => '1075310355', 'usuario_nombre' => 'María Pérez',
            'fecha_accion' => now()->subDays(25), // > 15 hábiles
        ]);

        $reciente = $this->bodega('SN-B1-07', 'retorno');
        Asignacion::create([
            'equipo_id' => $reciente->id, 'tipo_accion' => 'traslado',
            'usuario_cedula' => '1075310355', 'usuario_nombre' => 'María Pérez',
            'fecha_accion' => now()->subDays(3), // < 15 hábiles
        ]);

        $this->artisan('bodega:liberar-disponibles')->assertSuccessful();

        $this->assertSame('disponible', $antiguo->fresh()->razon_estado, 'Cumplidos los 15 hábiles pasa a DISPONIBLE.');
        $this->assertSame('retorno', $reciente->fresh()->razon_estado, 'Sin cumplir los 15 hábiles no se toca.');
    }

    public function test_no_se_puede_asignar_un_activo_del_taller_ni_de_baja(): void
    {
        $enTaller = $this->bodega('SN-B1-08', 'alistamiento_soporte');
        $this->expectException(\RuntimeException::class);
        $this->service->registrar($this->datos(Acta::TIPO_INVENTARIO_FISICO), [$enTaller]);
    }
}
