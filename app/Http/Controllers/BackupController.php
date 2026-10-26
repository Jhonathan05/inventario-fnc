<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

    /**
     * Restaura un respaldo subido en la petición (.sql.gz / .sql).
     */
    public function restaurarSubido(Request $request): JsonResponse|RedirectResponse
    {
        set_time_limit(0);

        $request->validate([
            'archivo' => [
                'required',
                'file',
                'max:' . (int) config('backups.max_subida_kb', 65536),
            ],
        ]);

        $subido = $request->file('archivo');
        $original = strtolower($subido->getClientOriginalName());

        if (! str_ends_with($original, '.sql.gz') && ! str_ends_with($original, '.gz') && ! str_ends_with($original, '.sql')) {
            return $this->respuestaRestaurar(
                $request,
                false,
                'Formato no admitido: sube el volcado .sql.gz generado por el módulo (o su .sql).'
            );
        }

        // El archivo subido ya está en disco (tmp de PHP); se restaura desde ahí.
        return $this->ejecutarRestaurar($request, (string) $subido->getRealPath());
    }

    /**
     * Restaura un respaldo ya guardado en la lista del módulo.
     */
    public function restaurarExistente(Request $request, string $archivo): JsonResponse|RedirectResponse
    {
        set_time_limit(0);

        $ruta = $this->backups->rutaDe($archivo);
        abort_if($ruta === null, 404, 'Ese backup no existe.');

        return $this->ejecutarRestaurar($request, $ruta);
    }

    private function ejecutarRestaurar(Request $request, string $ruta): JsonResponse|RedirectResponse
    {
        try {
            $resultado = $this->backups->restaurar($ruta);
        } catch (Throwable $e) {
            report($e);

            return $this->respuestaRestaurar($request, false, $e->getMessage());
        }

        // La restauración reemplazó roles/permisos: que Spatie relea la BD.
        try {
            Artisan::call('permission:cache-reset');
        } catch (Throwable) {
            // sin spatie o sin el comando, no es bloqueante
        }

        return $this->respuestaRestaurar(
            $request,
            true,
            sprintf(
                'Respaldo restaurado: %d tablas en %.1f s. Retorno automático: %s.',
                $resultado['tablas'],
                $resultado['duracion'],
                $resultado['retorno']
            ),
            $resultado
        );
    }

    /**
     * JSON para el flujo con fetch del módulo; redirect + flash si llega
     * como formulario normal.
     */
    private function respuestaRestaurar(
        Request $request,
        bool $ok,
        string $mensaje,
        ?array $resultado = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(
                $ok
                    ? ['success' => $mensaje, 'resultado' => $resultado]
                    : ['error' => $mensaje],
                $ok ? 200 : 422
            );
        }

        return redirect()
            ->route('backups.index')
            ->with($ok ? 'success' : 'error', $mensaje);
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
