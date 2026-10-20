<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ifsnop\Mysqldump\Mysqldump;

/**
 * Backups de la base de datos del inventario.
 *
 * Genera volcados MySQL comprimidos (gzip) 100 % en PHP con
 * ifsnop/mysqldump-php — sin depender del binario mysqldump del contenedor —
 * los guarda en el disco privado configurado (nunca público) y aplica la
 * retención tras cada creación.
 */
class BackupService
{
    public function directorio(): string
    {
        return (string) config('backups.directorio', 'backups');
    }

    /**
     * Crea un backup y devuelve sus metadatos.
     *
     * @return array{nombre: string, bytes: int, duracion: float, tablas: int, eliminados: int}
     *
     * @throws Exception
     */
    public function crear(): array
    {
        $storage = $this->storage();
        $directorio = $this->directorio();
        $storage->makeDirectory($directorio);

        $nombre = sprintf(
            '%s_backup_%s.sql.gz',
            config('backups.prefijo', 'inventario-fnc'),
            now()->format('Y_m_d_His')
        );
        $ruta = $directorio . '/' . $nombre;

        $conexion = config('database.connections.mysql');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $conexion['host'],
            $conexion['port'] ?? 3306,
            $conexion['database']
        );

        $tablas = count(DB::select('SHOW TABLES'));
        $inicio = microtime(true);

        $dump = new Mysqldump(
            $dsn,
            (string) $conexion['username'],
            (string) $conexion['password'],
            [
                'compress' => Mysqldump::GZIP,
                'add-locks' => true,
                'default-character-set' => (string) ($conexion['charset'] ?? 'utf8mb4'),
                'single-transaction' => true,
            ]
        );
        $dump->start($storage->path($ruta));

        $eliminados = $this->aplicarRetencion();

        return [
            'nombre' => $nombre,
            'bytes' => (int) $storage->size($ruta),
            'duracion' => (float) round(microtime(true) - $inicio, 1),
            'tablas' => $tablas,
            'eliminados' => $eliminados,
        ];
    }

    /**
     * Backups existentes, del más reciente al más antiguo.
     *
     * @return Collection<int, array{nombre: string, bytes: int, fecha: int}>
     */
    public function listar(): Collection
    {
        $storage = $this->storage();
        $directorio = $this->directorio();

        return collect($storage->files($directorio))
            ->map(fn (string $ruta): string => basename($ruta))
            ->filter(fn (string $nombre): bool => $this->coincidePatron($nombre))
            ->map(fn (string $nombre): array => [
                'nombre' => $nombre,
                'bytes' => (int) $storage->size($directorio . '/' . $nombre),
                'fecha' => (int) $storage->lastModified($directorio . '/' . $nombre),
            ])
            ->sortByDesc('nombre')
            ->values();
    }

    public function existe(string $nombre): bool
    {
        return $this->nombreValido($nombre)
            && $this->storage()->exists($this->directorio() . '/' . $nombre);
    }

    public function eliminar(string $nombre): bool
    {
        if (! $this->existe($nombre)) {
            return false;
        }

        return $this->storage()->delete($this->directorio() . '/' . $nombre);
    }

    /**
     * Retención: conserva sólo los N backups más recientes.
     *
     * @return int Cantidad de backups eliminados por exceder la retención.
     */
    public function aplicarRetencion(): int
    {
        $retencion = max(1, (int) config('backups.retencion', 15));
        $sobrantes = $this->listar()->slice($retencion);

        foreach ($sobrantes as $backup) {
            $this->eliminar((string) $backup['nombre']);
        }

        return $sobrantes->count();
    }

    /**
     * Rechaza rutas relativas o caracteres peligrosos (path traversal).
     */
    public function nombreValido(string $nombre): bool
    {
        return $nombre !== ''
            && ! str_contains($nombre, '/')
            && ! str_contains($nombre, '\\')
            && ! str_contains($nombre, '..')
            && (bool) preg_match('/^[A-Za-z0-9._-]+$/', $nombre);
    }

    private function coincidePatron(string $nombre): bool
    {
        $prefijo = preg_quote((string) config('backups.prefijo', 'inventario-fnc'), '/');

        return (bool) preg_match('/^' . $prefijo . '_backup_\d{4}_\d{2}_\d{2}_\d{6}\.sql\.gz$/', $nombre);
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function storage()
    {
        return Storage::disk((string) config('backups.disco', 'local'));
    }
}
