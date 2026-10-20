<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Gestión de backups de la base de datos (módulo de Configuración).
 *
 * Todo el módulo queda tras el permiso `configuracion.editar`; los archivos
 * viven en el disco privado y sólo se descargan a través del controlador.
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index()
    {
        return view('backups.index', [
            'backups' => $this->backups->listar(),
            'retencion' => (int) config('backups.retencion', 15),
            'programado' => (bool) config('backups.programado', false),
            'prefijo' => (string) config('backups.prefijo', 'inventario-fnc'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $backup = $this->backups->crear();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('backups.index')
                ->with('error', 'El backup no pudo crearse: ' . $e->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with(
                'success',
                sprintf(
                    'Backup %s creado (%s, %d tablas, %.1f s).',
                    $backup['nombre'],
                    $this->legible($backup['bytes']),
                    $backup['tablas'],
                    $backup['duracion']
                )
            );
    }

    public function descargar(string $archivo): StreamedResponse
    {
        abort_unless($this->backups->existe($archivo), 404);

        return Storage::disk((string) config('backups.disco', 'local'))->download(
            $this->backups->directorio() . '/' . $archivo,
            $archivo,
            ['Content-Type' => 'application/gzip']
        );
    }

    public function destroy(string $archivo): RedirectResponse
    {
        abort_unless($this->backups->existe($archivo), 404);

        $this->backups->eliminar($archivo);

        return redirect()
            ->route('backups.index')
            ->with('success', "Backup {$archivo} eliminado.");
    }

    private function legible(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $valor = (float) $bytes;
        $i = 0;

        while ($valor >= 1024 && $i < count($unidades) - 1) {
            $valor /= 1024;
            $i++;
        }

        return round($valor, 1) . ' ' . $unidades[$i];
    }
}
