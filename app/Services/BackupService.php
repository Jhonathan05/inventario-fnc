<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ifsnop\Mysqldump\Mysqldump;
use Throwable;

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

    /**
     * Ruta absoluta (disco privado) de un backup guardado.
     */
    public function rutaDe(string $nombre): ?string
    {
        if (! $this->existe($nombre)) {
            return null;
        }

        return $this->storage()->path($this->directorio() . '/' . $nombre);
    }

    /**
     * Restaura un volcado (.sql.gz o .sql) sobre la base de datos actual.
     *
     * Contrato de seguridad, en orden:
     *  1. Antes de tocar nada crea un backup de RETORNO automático.
     *  2. Vacía las tablas actuales (el volcado trae CREATE TABLE sin DROP).
     *  3. Importa el volcado completo (FK checks desactivados durante el pase).
     *  4. Verifica que los campos cifrados se puedan leer con la APP_KEY
     *     actual; si el volcado viene de otra instancia con otra key, revierte
     *     solo con el backup de retorno y lanza el error.
     *
     * @return array{tablas: int, duracion: float, retorno: string}
     *
     * @throws Exception
     */
    public function restaurar(string $rutaAbsoluta): array
    {
        if (! is_file($rutaAbsoluta)) {
            throw new Exception("No se encontró el volcado a restaurar: {$rutaAbsoluta}");
        }

        $this->assertVolcadoValido($rutaAbsoluta);

        $inicio = microtime(true);

        // 1. Punto de retorno automático.
        $retorno = $this->crear();
        $rutaRetorno = $this->rutaDe($retorno['nombre']);

        try {
            // 2 y 3. Vaciar e importar.
            $this->importar($rutaAbsoluta);

            // 4. ¿Sirve este volcado con la APP_KEY actual?
            $this->verificarCifrado();
        } catch (Throwable $e) {
            try {
                if (is_string($rutaRetorno) && is_file($rutaRetorno)) {
                    $this->importar($rutaRetorno);
                }
            } catch (Throwable $revertir) {
                report($revertir);

                throw new Exception(
                    'La restauración falló (' . $e->getMessage() . ') y el retorno automático también falló: '
                    . $revertir->getMessage()
                    . '. El backup manual de retorno es «' . $retorno['nombre'] . '».',
                    0,
                    $e
                );
            }

            throw new Exception(
                $e->getMessage()
                . ' — la base de datos quedó en su estado anterior (retorno automático: '
                . $retorno['nombre'] . ').',
                0,
                $e
            );
        }

        return [
            'tablas' => count(DB::select('SHOW TABLES')),
            'duracion' => (float) round(microtime(true) - $inicio, 1),
            'retorno' => $retorno['nombre'],
        ];
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
     * El archivo existe y parece un volcado MySQL del aplicativo.
     *
     * @throws Exception
     */
    private function assertVolcadoValido(string $ruta): void
    {
        $gzip = ! str_ends_with(strtolower($ruta), '.sql');
        $manejador = $gzip ? @gzopen($ruta, 'rb') : @fopen($ruta, 'rb');

        if ($manejador === false) {
            throw new Exception('El archivo no se pudo abrir como volcado (¿es un gzip válido?).');
        }

        $cabecera = '';

        try {
            while (($linea = $gzip ? gzgets($manejador) : fgets($manejador)) !== false) {
                $cabecera .= $linea;

                if (strlen($cabecera) > 131072) {
                    break;
                }
            }
        } finally {
            $gzip ? gzclose($manejador) : fclose($manejador);
        }

        if (
            ! str_contains($cabecera, 'CREATE TABLE')
            && ! str_contains($cabecera, 'mysqldump-php')
            && ! str_contains($cabecera, 'MySQL dump')
        ) {
            throw new Exception('El archivo no parece un volcado MySQL del aplicativo.');
        }
    }

    /**
     * Vacía las tablas actuales e importa el volcado completo.
     *
     * @throws Exception
     */
    private function importar(string $ruta): void
    {
        $gzip = ! str_ends_with(strtolower($ruta), '.sql');
        $manejador = $gzip ? @gzopen($ruta, 'rb') : @fopen($ruta, 'rb');

        if ($manejador === false) {
            throw new Exception('No se pudo abrir el volcado para importar.');
        }

        $pdo = DB::connection()->getPdo();

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            // El volcado trae CREATE TABLE sin DROP: se vacía todo antes.
            $tablas = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tablas as $tabla) {
                $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', (string) $tabla) . '`');
            }

            foreach ($this->instrucciones($manejador, $gzip) as $instruccion) {
                $pdo->exec($instruccion);
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } finally {
            $gzip ? gzclose($manejador) : fclose($manejador);
        }
    }

    /**
     * Lee el volcado línea a línea y produce cada sentencia completa.
     * Los volcados de mysqldump-php escapan los saltos de línea dentro de los
     * valores, así que una sentencia termina justo donde la línea acaba en «;».
     */
    private function instrucciones($manejador, bool $gzip): \Generator
    {
        $buffer = '';

        while (($linea = $gzip ? gzgets($manejador) : fgets($manejador)) !== false) {
            $linea = rtrim($linea, "\r\n");

            // Comentarios y líneas vacías no entran al buffer.
            if ($buffer === '' && ($linea === '' || str_starts_with($linea, '--'))) {
                continue;
            }

            $buffer .= ($buffer === '' ? '' : "\n") . $linea;

            if (str_ends_with($linea, ';')) {
                yield $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            yield $buffer;
        }
    }

    /**
     * ¿Los datos cifrados del volcado se leen con la APP_KEY actual?
     *
     * Un volcado de otra instancia (con otra APP_KEY) dejaría ilegibles
     * nombres, cédulas y correos, y rompería los hash de búsqueda por cédula.
     *
     * @throws Exception
     */
    private function verificarCifrado(): void
    {
        try {
            $muestra = DB::table('funcionarios')->whereNotNull('nombres')->value('nombres');
        } catch (Throwable) {
            return; // sin la tabla no hay nada que verificar
        }

        if ($muestra === null || $muestra === '') {
            return;
        }

        try {
            // El cast «encrypted» guarda el valor sin serializar: unserialize=false.
            decrypt((string) $muestra, false);
        } catch (Throwable) {
            throw new Exception(
                'El volcado viene de una instancia con APP_KEY distinta: sus datos cifrados '
                . 'quedarían ilegibles con la llave actual.'
            );
        }
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function storage()
    {
        return Storage::disk((string) config('backups.disco', 'local'));
    }
}
