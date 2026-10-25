<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use App\Services\Importadores\CMDBMapperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Rellena `equipos.fila_cmdb` con la fila REAL del archivo CMDB.
 *
 * El `consecutivo` es un número interno del sistema y se desalinea cuando el
 * importador descarta duplicados; no sirve para ubicar el registro en el
 * Excel. Este comando empareja cada activo con su fila por identificadores
 * estables (placa, serial y activo fijo, en ese orden) y es idempotente.
 *
 * Uso:
 *   php artisan cmdb:backfill-fila "docs/Formatos/CMDB USUARIO FINAL.xlsx"
 *   php artisan cmdb:backfill-fila --dry-run
 */
class BackfillFilaCmdb extends Command
{
    protected $signature = 'cmdb:backfill-fila
        {archivo? : Ruta del Excel del CMDB}
        {--sheet=Microinformatica : Hoja del CMDB}
        {--dry-run : Muestra el resultado sin escribir}';

    protected $description = 'Rellena equipos.fila_cmdb con la fila real del CMDB (empareja por placa, serial o activo fijo)';

    public function handle(): int
    {
        $archivo = $this->argument('archivo') ?: base_path('docs/Formatos/CMDB USUARIO FINAL.xlsx');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");
            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($archivo);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($archivo);
        $hoja = $spreadsheet->getSheetByName($this->option('sheet')) ?: $spreadsheet->getActiveSheet();

        [$filaEncabezado, $colSerial, $colPlaca] = $this->detectarEncabezado($hoja);
        if ($filaEncabezado === null) {
            $this->error('No se encontraron las columnas SERIAL y PLACA en la hoja.');
            return self::FAILURE;
        }

        // Mapa identificador normalizado => fila del Excel (la primera aparición
        // gana, en línea con el contrato A1: se conserva la primera fila).
        $mapa = [];
        $ultimaFila = $hoja->getHighestDataRow();

        for ($fila = $filaEncabezado + 1; $fila <= $ultimaFila; $fila++) {
            $claves = [
                CMDBMapperService::normalizeIdentifier($this->texto($hoja->getCell($colPlaca . $fila)->getValue())),
                CMDBMapperService::normalizeIdentifier($this->texto($hoja->getCell($colSerial . $fila)->getValue())),
            ];

            foreach ($claves as $clave) {
                if ($clave !== null && ! isset($mapa[$clave])) {
                    $mapa[$clave] = $fila;
                }
            }
        }

        $this->info(sprintf(
            'CMDB: encabezado en la fila %d, %d filas de datos, %d identificadores.',
            $filaEncabezado,
            max(0, $ultimaFila - $filaEncabezado),
            count($mapa)
        ));

        $equipos = Equipo::query()->get(['id', 'placa', 'serial', 'activo_fijo', 'fila_cmdb']);
        $actualizados = 0;
        $sinFila = 0;

        foreach ($equipos as $equipo) {
            $fila = $this->filaDe($equipo, $mapa);

            if ($fila === null) {
                $sinFila++;
                continue;
            }

            if ((int) $equipo->fila_cmdb === $fila) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf('  %s (%s) → fila %d', $equipo->placa ?: $equipo->serial, $equipo->id, $fila));
            } else {
                DB::table('equipos')->where('id', $equipo->id)->update(['fila_cmdb' => $fila]);
            }

            $actualizados++;
        }

        $this->info(sprintf(
            '%s %d de %d activos. Sin coincidencia: %d.',
            $this->option('dry-run') ? 'Se actualizarían' : 'Actualizados',
            $actualizados,
            $equipos->count(),
            $sinFila
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: string|null}
     */
    private function detectarEncabezado($hoja): array
    {
        $colSerial = $colPlaca = null;

        foreach (range(1, 10) as $fila) {
            $colSerial = $colPlaca = null;
            $iterador = $hoja->getRowIterator($fila, $fila)->current()->getCellIterator();
            $iterador->setIterateOnlyExistingCells(false);

            foreach ($iterador as $celda) {
                $valor = mb_strtoupper(trim((string) $celda->getValue()));
                if ($valor === 'SERIAL') {
                    $colSerial = $celda->getColumn();
                } elseif ($valor === 'PLACA') {
                    $colPlaca = $celda->getColumn();
                }
            }

            if ($colSerial !== null && $colPlaca !== null) {
                return [$fila, $colSerial, $colPlaca];
            }
        }

        return [null, null, null];
    }

    /**
     * @param  array<string, int>  $mapa
     */
    private function filaDe(Equipo $equipo, array $mapa): ?int
    {
        $claves = [
            CMDBMapperService::normalizeIdentifier($equipo->placa),
            CMDBMapperService::normalizeIdentifier($equipo->serial),
            CMDBMapperService::normalizeIdentifier($equipo->activo_fijo),
        ];

        foreach ($claves as $clave) {
            if ($clave !== null && isset($mapa[$clave])) {
                return $mapa[$clave];
            }
        }

        return null;
    }

    /** Evita que un serial numérico de Excel llegue en notación científica. */
    private function texto(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return sprintf('%.0f', $valor);
        }

        return trim((string) $valor);
    }
}
