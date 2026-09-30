<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PdfCompressor
{
    /**
     * Intenta comprimir un archivo PDF utilizando Ghostscript si está disponible.
     * Retorna la ruta del archivo (puede ser el comprimido o el original).
     *
     * @param string $sourcePath Ruta absoluta del archivo PDF original
     * @param string $targetPath Ruta absoluta donde guardar el PDF comprimido
     * @return string Ruta del archivo final
     */
    public static function compress(string $sourcePath, string $targetPath): string
    {
        // Detectar si el sistema operativo es Windows o Linux para el comando
        $gsCommand = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'gswin64c' : 'gs';

        // Comprobar si el comando ghostscript está disponible
        $checkCommand = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "where $gsCommand 2>nul" : "which $gsCommand 2>/dev/null";
        exec($checkCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::info("PdfCompressor: Ghostscript no está disponible ($gsCommand). Se omitirá la compresión.");
            return $sourcePath;
        }

        // Ejecutar compresión (calidad ebook que ofrece buena relación tamaño/legibilidad)
        $command = escapeshellcmd($gsCommand) . 
                   " -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH " . 
                   "-sOutputFile=" . escapeshellarg($targetPath) . " " . 
                   escapeshellarg($sourcePath);

        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
            Log::info("PdfCompressor: Compresión exitosa.");
            return $targetPath;
        }

        Log::error("PdfCompressor: Falló la compresión. Se mantendrá el original.");
        return $sourcePath;
    }
}
