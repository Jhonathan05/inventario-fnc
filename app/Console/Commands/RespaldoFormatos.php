<?php

namespace App\Console\Commands;

use App\Domain\Activos\EstadoOperativo;
use App\Domain\Activos\RazonEstado;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\Territorio;
use App\Models\TipoRecurso;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Respaldo de arranque (Lote B4): inventario, funcionarios y campos de los
 * formatos (catálogos) como Excel — el punto de restauración del aplicativo
 * tras la importación única del CMDB.
 *
 * Uso:  php artisan respaldo:formatos
 * Archivos en storage/app/respaldos/:
 *   respaldo_inventario_<fecha>.xlsx
 *   respaldo_funcionarios_<fecha>.xlsx
 *   respaldo_catalogos_<fecha>.xlsx
 */
class RespaldoFormatos extends Command
{
    protected $signature = 'respaldo:formatos';

    protected $description = 'Genera los respaldos de arranque: inventario, funcionarios y catálogos (Excel)';

    /** @return array<int, string> rutas de los archivos generados */
    public function handle(): array
    {
        $dir = storage_path('app/respaldos');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fecha = now()->format('Ymd_His');
        $rutas = [
            'inventario'   => "{$dir}/respaldo_inventario_{$fecha}.xlsx",
            'funcionarios' => "{$dir}/respaldo_funcionarios_{$fecha}.xlsx",
            'catalogos'    => "{$dir}/respaldo_catalogos_{$fecha}.xlsx",
        ];

        $this->inventario($rutas['inventario']);
        $this->funcionarios($rutas['funcionarios']);
        $this->catalogos($rutas['catalogos']);

        foreach ($rutas as $nombre => $ruta) {
            $this->info("✔ {$nombre}: " . basename($ruta));
        }

        return array_values($rutas);
    }

    /* ── Inventario: activos + estado + custodia (columnas del formato) ── */

    private function inventario(string $ruta): void
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Inventario');

        $hoja->fromArray([[
            'PLACA', 'SERIAL', 'TIPO', 'MARCA', 'MODELO', 'NOMBRE DE EQUIPO',
            'ESTADO OPERATIVO', 'RAZÓN DEL ESTADO', 'ESTADO DE USO',
            'ADMINISTRADO', 'TIPO DE PROPIEDAD', 'CHECKLIST RESPONSABLE TI',
            'ORDEN DE REMISIÓN', 'OBSERVACIONES',
            'CEDULA DEL CUSTODIO', 'NOMBRES Y APELLIDOS DEL CUSTODIO',
            'CARGO', 'ÁREA', 'CIUDAD', 'UBICACIÓN Y PISO',
        ]], null, 'A1');

        Equipo::with(['tipoRecurso', 'usuarioAsignado'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(500, function ($equipos) use ($hoja) {
                foreach ($equipos as $equipo) {
                    $ua = $equipo->usuarioAsignado;
                    $hoja->fromArray([[
                        $equipo->placa,
                        $equipo->serial,
                        $equipo->tipoRecurso?->nombre,
                        $equipo->marca,
                        $equipo->modelo,
                        $equipo->nombre_equipo,
                        EstadoOperativo::label($equipo->estado_operativo),
                        RazonEstado::label($equipo->razon_estado),
                        $equipo->estado_uso,
                        $equipo->administrado,
                        $equipo->tipo_propiedad,
                        $equipo->checklist_responsable,
                        $equipo->orden_remision,
                        $equipo->observaciones,
                        $ua?->cedula,
                        $ua?->nombre,
                        $ua?->cargo,
                        $ua?->area,
                        $ua?->ciudad,
                        $ua?->piso,
                    ]], null, 'A' . ($hoja->getHighestRow() + 1));
                }
            });

        (new Xlsx($libro))->save($ruta);
    }

    /* ── Funcionarios: maestro completo ─────────────────────────────────── */

    private function funcionarios(string $ruta): void
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Funcionarios');

        $hoja->fromArray([[
            'CEDULA', 'NOMBRES', 'APELLIDOS', 'CORREO', 'CARGO', 'ÁREA',
            'EMPRESA', 'DIV', 'DEPENDENCIA', 'SEDE (CIUDAD)', 'GÉNERO',
            'JEFE CEDULA', 'JEFE NOMBRE', 'JEFE CORREO', 'CECO SAP',
            'FECHA VENCIMIENTO', 'PROCESO', 'TIPO DE RECURSO', 'ESTADO',
        ]], null, 'A1');

        Funcionario::query()->orderBy('id')
            ->chunk(500, function ($filas) use ($hoja) {
                foreach ($filas as $f) {
                    $hoja->fromArray([[
                        $f->identificacion,
                        $f->nombres,
                        $f->apellidos,
                        $f->email,
                        $f->cargo,
                        $f->area,
                        $f->empresa_funcionario,
                        $f->div,
                        $f->departamento,
                        $f->ciudad,
                        $f->genero,
                        $f->jefe_cedula,
                        $f->jefe_nombre,
                        $f->jefe_email,
                        $f->ceco,
                        $f->fecha_vencimiento?->format('Y-m-d'),
                        $f->proceso,
                        $f->tipo_recurso,
                        $f->estado,
                    ]], null, 'A' . ($hoja->getHighestRow() + 1));
                }
            });

        (new Xlsx($libro))->save($ruta);
    }

    /* ── Campos de los formatos: todas las listas editables ─────────────── */

    private function catalogos(string $ruta): void
    {
        $libro = new Spreadsheet();

        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Estados');
        $hoja->fromArray([['CODIGO', 'NOMBRE', 'DESCRIPCION']], null, 'A1');
        foreach (DB::table('catalogo_estados')->orderBy('id')->get() as $e) {
            $hoja->fromArray([[$e->codigo, $e->nombre, $e->descripcion]], null, 'A' . ($hoja->getHighestRow() + 1));
        }

        $hoja = $libro->createSheet();
        $hoja->setTitle('Razones');
        $hoja->fromArray([['CODIGO', 'NOMBRE', 'DESCRIPCION']], null, 'A1');
        foreach (DB::table('catalogo_razones')->orderBy('id')->get() as $r) {
            $hoja->fromArray([[$r->codigo, $r->nombre, $r->descripcion]], null, 'A' . ($hoja->getHighestRow() + 1));
        }

        $hoja = $libro->createSheet();
        $hoja->setTitle('Diccionarios');
        $hoja->fromArray([['GRUPO', 'VALOR', 'ACTIVO']], null, 'A1');
        foreach (DB::table('diccionarios')->orderBy('grupo')->orderBy('valor')->get() as $d) {
            $hoja->fromArray([[$d->grupo, $d->valor, $d->activo ? 'Sí' : 'No']], null, 'A' . ($hoja->getHighestRow() + 1));
        }

        $hoja = $libro->createSheet();
        $hoja->setTitle('Territorio');
        $hoja->fromArray([['CIRCUNSCRIPCION', 'SECCIONAL', 'MUNICIPIO', 'ACTIVO']], null, 'A1');
        foreach (Territorio::orderBy('circunscripcion')->orderBy('seccional')->get(['circunscripcion', 'seccional', 'municipio', 'activo']) as $t) {
            $hoja->fromArray([[$t->circunscripcion, $t->seccional, $t->municipio, $t->activo ? 'Sí' : 'No']], null, 'A' . ($hoja->getHighestRow() + 1));
        }

        $hoja = $libro->createSheet();
        $hoja->setTitle('Tipos de recurso');
        $hoja->fromArray([['NOMBRE', 'ES COMPUTADOR', 'CATÁLOGO CMDB', 'ACTIVO']], null, 'A1');
        foreach (TipoRecurso::orderBy('nombre')->get() as $t) {
            $hoja->fromArray([[$t->nombre, $t->es_computador ? 'Sí' : 'No', $t->es_catalogo_cmdb ? 'Sí' : 'No', ($t->activo ?? true) ? 'Sí' : 'No']], null, 'A' . ($hoja->getHighestRow() + 1));
        }

        (new Xlsx($libro))->save($ruta);
    }
}
