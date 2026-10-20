<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        config(['backups.retencion' => 5]);
        Storage::fake('local');
    }

    public function test_admin_ve_el_modulo_de_backups(): void
    {
        $this->actingAs($this->admin)
            ->get(route('backups.index'))
            ->assertOk()
            ->assertSee('Backups disponibles')
            ->assertSee('Crear backup ahora');
    }

    public function test_usuario_sin_permiso_no_entra(): void
    {
        $sinPermiso = User::factory()->create();

        $this->actingAs($sinPermiso)
            ->get(route('backups.index'))
            ->assertForbidden();
    }

    public function test_crear_descargar_y_eliminar_un_backup(): void
    {
        $this->actingAs($this->admin)
            ->post(route('backups.crear'))
            ->assertRedirect(route('backups.index'));

        $servicio = new BackupService;
        $backups = $servicio->listar();
        $this->assertCount(1, $backups);

        $nombre = $backups->first()['nombre'];
        $this->assertMatchesRegularExpression(
            '/^inventario-fnc_backup_\d{4}_\d{2}_\d{2}_\d{6}\.sql\.gz$/',
            $nombre
        );

        // El volcado es gzip válido con contenido SQL real.
        $contenido = gzdecode(
            (string) file_get_contents(Storage::disk('local')->path('backups/' . $nombre))
        );
        $this->assertStringContainsString('CREATE TABLE', $contenido);

        $this->actingAs($this->admin)
            ->get(route('backups.descargar', ['archivo' => $nombre]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/gzip');

        $this->actingAs($this->admin)
            ->from(route('backups.index'))
            ->delete(route('backups.eliminar', ['archivo' => $nombre]))
            ->assertRedirect(route('backups.index'));

        $this->assertCount(0, $servicio->listar());
    }

    public function test_nombres_invalidos_o_inexistentes_rechazados(): void
    {
        $this->actingAs($this->admin)
            ->get(route('backups.descargar', ['archivo' => 'inexistente.sql.gz']))
            ->assertNotFound();

        // Path traversal: el parámetro con '/' no casa con la restricción de ruta.
        $this->actingAs($this->admin)
            ->get('/backups/..%2F..%2F.env/descargar')
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete(route('backups.eliminar', ['archivo' => 'inexistente.sql.gz']))
            ->assertNotFound();
    }

    public function test_retencion_conserva_solo_los_mas_recientes(): void
    {
        $servicio = new BackupService;

        // 7 backups con nombres crecientes; la retención es 5.
        foreach (['2026_01_01_000000', '2026_01_02_000000', '2026_01_03_000000',
                     '2026_01_04_000000', '2026_01_05_000000', '2026_01_06_000000',
                     '2026_01_07_000000'] as $sello) {
            Storage::disk('local')->put(
                "backups/inventario-fnc_backup_{$sello}.sql.gz",
                'prueba'
            );
        }

        $eliminados = $servicio->aplicarRetencion();

        $this->assertSame(2, $eliminados);
        $restantes = $servicio->listar();
        $this->assertCount(5, $restantes);
        $this->assertSame('inventario-fnc_backup_2026_01_07_000000.sql.gz', $restantes->first()['nombre']);
        $this->assertSame('inventario-fnc_backup_2026_01_03_000000.sql.gz', $restantes->last()['nombre']);
    }
}
