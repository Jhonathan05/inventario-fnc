<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class EquiposImportSelector implements WithMultipleSheets
{
    private string $filePath;
    private string $responsableInstitucional;
    private ?EquiposImport $selectedImport = null;

    public function __construct(string $filePath, string $responsableInstitucional)
    {
        $this->filePath = $filePath;
        $this->responsableInstitucional = $responsableInstitucional;
    }

    public function getImport(): ?EquiposImport
    {
        return $this->selectedImport;
    }

    public function sheets(): array
    {
        Log::info("AUDITORIA IMPORTACIÓN - INICIO DE SELECCIÓN DE HOJA");
        $reader = IOFactory::createReaderForFile($this->filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($this->filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        
        $bestSheetName = $sheetNames[0];
        $bestScore = 0;
        
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            $score = 0;
            
            for ($row = 1; $row <= 5; $row++) {
                try {
                    $cellIterator = $sheet->getRowIterator($row, $row)->current()->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    $rowScore = 0;
                    foreach ($cellIterator as $cell) {
                        $valRaw = $cell->getValue();
                        $val = strtolower(trim((string) $valRaw));
                        if ($val) {
                            if (preg_match('/serial|marca|modelo|tipo.*recurso|estado|funcionario|responsable|cedula|identificaci|nombre.*equipo/i', $val)) {
                                $rowScore++;
                            }
                        }
                    }
                    if ($rowScore > $score) {
                        $score = $rowScore;
                    }
                } catch (\Exception $e) {
                    continue; // Skip if row doesn't exist
                }
            }
            
            Log::info("Hoja analizada: '{$name}' - Puntaje: {$score}");
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSheetName = $name;
            }
        }
        
        if ($bestScore < 3) {
            throw new \RuntimeException("Ninguna de las hojas en el archivo Excel parece contener el formato correcto de Equipos o CMDB. Asegúrate de incluir las columnas principales (serial, marca, modelo, etc.).");
        }
        
        Log::info("Hoja seleccionada: '{$bestSheetName}' con puntaje {$bestScore}");
        
        $this->selectedImport = new EquiposImport($this->filePath, $this->responsableInstitucional, $bestSheetName);
        
        return [
            $bestSheetName => $this->selectedImport
        ];
    }
}
