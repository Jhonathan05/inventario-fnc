<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Respaldo de arranque (Lote B4) — descarga los tres Excel del punto de
 * restauración: inventario, funcionarios y campos de los formatos
 * (catálogos), empaquetados en ZIP.
 */
class RespaldoController extends Controller
{
    public function formatos(Request $request): BinaryFileResponse
    {
        // Genera (o regenera) los tres respaldos.
        Artisan::call('respaldo:formatos');
        $rutas = Artisan::output();

        $archivos = collect(glob(storage_path('app/respaldos/respaldo_*.xlsx')))
            ->sortByDesc(fn ($p) => filemtime($p))
            ->take(3)
            ->values();

        abort_if($archivos->isEmpty(), 500, 'No se pudieron generar los respaldos.');

        $zipRuta = storage_path('app/respaldos/respaldo_formatos_' . now()->format('Ymd_His') . '.zip');
        $zip = new ZipArchive();
        $zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($archivos as $archivo) {
            $zip->addFile($archivo, basename($archivo));
        }
        $zip->close();

        return response()->download($zipRuta)->deleteFileAfterSend();
    }
}
