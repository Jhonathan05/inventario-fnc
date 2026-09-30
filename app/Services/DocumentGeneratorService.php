<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Equipo;
use App\Models\Funcionario;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Auth;

class DocumentGeneratorService
{
    protected string $formatosDir;

    public function __construct()
    {
        $this->formatosDir = storage_path('app/formatos');
    }

    /**
     * Genera el Certificado de Responsabilidad en formato Word (.docx)
     */
    public function generarResponsabilidadDocx(Asignacion $asignacion): string
    {
        $templatePath = $this->formatosDir . '/responsabilidad.docx';
        if (!file_exists($templatePath)) {
            throw new \Exception('La plantilla de responsabilidad.docx no se encuentra en ' . $templatePath);
        }

        $asignacion->load(['equipo.tipoRecurso']);

        $tempPath = storage_path('app/temp_resp_' . uniqid() . '.docx');
        copy($templatePath, $tempPath);

        $zip = new ZipArchive();
        if ($zip->open($tempPath) !== true) {
            throw new \Exception('No se pudo abrir el archivo Word temporal.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!$xml) {
            $zip->close();
            @unlink($tempPath);
            throw new \Exception('No se pudo leer document.xml de la plantilla Word.');
        }

        $fechaStr = $asignacion->fecha_accion?->format('Y-m-d') ?? now()->format('Y-m-d');
        $nombreFuncionario = $asignacion->usuario_nombre ?? '—';
        $cedulaFuncionario = $asignacion->usuario_cedula ? 'C.C. ' . $asignacion->usuario_cedula : '—';
        $dependenciaFuncionario = $asignacion->usuario_dependencia ?? $asignacion->usuario_area ?? 'N/A';

        // Reemplazar valores en la segunda columna de la tabla de firmas
        $xml = $this->replaceDocxTableValue($xml, 'Nombres y Apellidos', $nombreFuncionario);
        $xml = $this->replaceDocxTableValue($xml, 'Tipo y No. Identificación', $cedulaFuncionario);
        $xml = $this->replaceDocxTableValue($xml, 'Dependencia o centro logístico', $dependenciaFuncionario);
        $xml = $this->replaceDocxTableValue($xml, 'Fecha', $fechaStr);

        // Armar bloque de especificaciones del equipo
        $equipo = $asignacion->equipo;
        $newParagraphsXml = '';

        if ($equipo) {
            $newParagraphsXml .= $this->createDocxParagraph('INFORMACIÓN DEL EQUIPO ENTREGADO COMO HERRAMIENTA DE TRABAJO:', true);
            $newParagraphsXml .= $this->createDocxParagraph('- Tipo de Equipo: ' . ($equipo->tipoRecurso?->nombre ?? 'N/A'));
            $newParagraphsXml .= $this->createDocxParagraph('- Marca / Modelo: ' . trim(($equipo->marca ?? '') . ' ' . ($equipo->modelo ?? '')));
            $newParagraphsXml .= $this->createDocxParagraph('- Serial: ' . ($equipo->serial ?? 'N/A'));
            $newParagraphsXml .= $this->createDocxParagraph('- Placa de Inventario: ' . ($equipo->placa ?? $equipo->activo_fijo ?? 'N/A'));
            $newParagraphsXml .= $this->createDocxParagraph('- Nombre en Red (Hostname): ' . ($equipo->nombre_equipo ?? 'N/A'));
            $newParagraphsXml .= $this->createDocxParagraph('- Características: Procesador ' . ($equipo->procesador ?? 'N/A') . ', RAM ' . ($equipo->ram ?? 'N/A') . ', Disco ' . ($equipo->disco ?? 'N/A'));
            $newParagraphsXml .= $this->createDocxParagraph('- Observaciones de Entrega: ' . ($asignacion->motivo ?? 'Ninguna'));
            $newParagraphsXml .= $this->createDocxParagraph('');
        }

        $xml = $this->replaceDeclaroParagraph($xml, $newParagraphsXml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tempPath;
    }

    /**
     * Genera la Planilla de Novedad en formato Excel (.xlsx)
     */
    public function generarNovedadExcel(Asignacion $asignacion): string
    {
        $templatePath = $this->formatosDir . '/novedad.xlsx';
        if (!file_exists($templatePath)) {
            throw new \Exception('La plantilla de novedad.xlsx no se encuentra en ' . $templatePath);
        }

        $asignacion->load(['equipo.tipoRecurso']);
        $equipo = $asignacion->equipo;

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. HEADER (Fila 7)
        $tipoMapeado = match ($asignacion->tipo_accion) {
            'asignacion'   => 'Cambio de responsable',
            'devolucion'   => 'Traslado',
            'mantenimiento'=> 'Reparación',
            'baja'         => 'Retiro definitivo',
            'prestamo'     => 'Préstamo',
            default        => ucfirst($asignacion->tipo_accion ?? 'Asignación'),
        };

        $ciudadHeader = $asignacion->usuario_ciudad ?? 'Ibagué';
        $fechaFormatted = $asignacion->fecha_accion?->format('Y/m/d') ?? now()->format('Y/m/d');

        $sheet->setCellValue('E7', $tipoMapeado);
        $sheet->setCellValue('H7', $ciudadHeader);
        $sheet->setCellValue('L7', $fechaFormatted);

        // 2. TABLA DE ACTIVOS (Fila 11 -> Indice 11)
        if ($equipo) {
            $sheet->setCellValue('C11', $equipo->activo_fijo ?? $equipo->placa ?? 'N/A');
            $sheet->setCellValue('D11', 1);
            $sheet->setCellValue('E11', $equipo->tipoRecurso?->nombre ?? 'EQUIPO');
            $sheet->setCellValue('G11', $equipo->marca ?? '');
            $sheet->setCellValue('H11', $equipo->modelo ?? '');
            $sheet->setCellValue('I11', $equipo->serial ?? '');
            $sheet->setCellValue('J11', $equipo->placa ?? '');
            $sheet->setCellValue('K11', $asignacion->motivo ?? '');
        }

        // 3. FIRMAS (Filas 25 y 28)
        $usuarioSistema = Auth::user()?->name ?? 'Analista TIC';

        if ($asignacion->tipo_accion === 'devolucion') {
            $entregador = [
                'dependencia'  => $asignacion->usuario_dependencia ?? 'N/A',
                'nombre'       => $asignacion->usuario_nombre ?? '',
                'cod_personal' => $asignacion->usuario_cedula ?? 'N/A',
                'cargo'        => $asignacion->usuario_cargo ?? 'N/A',
            ];
            $receptor = [
                'dependencia'  => 'TECNOLOGÍA DE INFORMACIÓN',
                'nombre'       => $usuarioSistema,
                'cod_personal' => 'N/A',
                'cargo'        => 'Analista de Soporte TIC',
            ];
        } else {
            $entregador = [
                'dependencia'  => 'TECNOLOGÍA DE INFORMACIÓN',
                'nombre'       => $usuarioSistema,
                'cod_personal' => 'N/A',
                'cargo'        => 'Analista de Soporte TIC',
            ];
            $receptor = [
                'dependencia'  => $asignacion->usuario_dependencia ?? 'N/A',
                'nombre'       => $asignacion->usuario_nombre ?? '',
                'cod_personal' => $asignacion->usuario_cedula ?? 'N/A',
                'cargo'        => $asignacion->usuario_cargo ?? 'N/A',
            ];
        }

        // Fila 25: Entregador
        $sheet->setCellValue('C25', $entregador['dependencia']);
        $sheet->setCellValue('E25', $entregador['nombre']);
        $sheet->setCellValue('J25', $entregador['cod_personal']);
        $sheet->setCellValue('K25', $entregador['cargo']);

        // Fila 28: Receptor
        $sheet->setCellValue('C28', $receptor['dependencia']);
        $sheet->setCellValue('E28', $receptor['nombre']);
        $sheet->setCellValue('J28', $receptor['cod_personal']);
        $sheet->setCellValue('K28', $receptor['cargo']);

        $tempPath = storage_path('app/temp_novedad_' . uniqid() . '.xlsx');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Helper para reemplazar celdas de firmas en la tabla de 2 columnas de Word.
     */
    protected function replaceDocxTableValue(string $xml, string $key, string $value): string
    {
        $rowRegex = '/<w:tr[\s\S]*?<\/w:tr>/';
        preg_match_all($rowRegex, $xml, $matches);

        foreach ($matches[0] as $rowXml) {
            preg_match_all('/<w:tc[\s\S]*?<\/w:tc>/', $rowXml, $cMatches);
            $cells = $cMatches[0] ?? [];

            if (count($cells) === 2 && str_contains($cells[0], $key)) {
                $secondCell = $cells[1];
                preg_match('/<w:tcPr[\s\S]*?<\/w:tcPr>/', $secondCell, $tcPrMatch);
                $tcPr = $tcPrMatch[0] ?? '';

                $newSecondCell = '<w:tc>' . $tcPr . '<w:p><w:pPr><w:spacing /><w:ind /><w:rPr><w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" /><w:sz w:val="20" /><w:szCs w:val="20" /></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" /><w:sz w:val="20" /><w:szCs w:val="20" /></w:rPr><w:t xml:space="preserve">' . htmlspecialchars($value) . '</w:t></w:r></w:p></w:tc>';

                $newRowXml = str_replace($secondCell, $newSecondCell, $rowXml);
                return str_replace($rowXml, $newRowXml, $xml);
            }
        }

        return $xml;
    }

    /**
     * Helper para construir un párrafo en XML de Word (Montserrat 10pt)
     */
    protected function createDocxParagraph(string $text, bool $isBold = false): string
    {
        $boldXml = $isBold ? '<w:b/>' : '';
        return '<w:p>' .
            '<w:pPr>' .
            '<w:spacing w:line="276" w:lineRule="auto" />' .
            '<w:ind w:right="257" />' .
            '<w:jc w:val="left" />' .
            '<w:rPr>' .
            '<w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" />' .
            '<w:color w:val="000000" />' .
            '<w:sz w:val="20" />' .
            '<w:szCs w:val="20" />' .
            $boldXml .
            '</w:rPr>' .
            '</w:pPr>' .
            '<w:r>' .
            '<w:rPr>' .
            '<w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" />' .
            '<w:color w:val="000000" />' .
            '<w:sz w:val="20" />' .
            '<w:szCs w:val="20" />' .
            $boldXml .
            '</w:rPr>' .
            '<w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>' .
            '</w:r>' .
            '</w:p>';
    }

    /**
     * Inserta los párrafos dinámicos antes del párrafo "Declaro y acepto..."
     */
    protected function replaceDeclaroParagraph(string $xml, string $newParagraphsXml): string
    {
        $keyword = 'Declaro y acepto el contenido de este documento.';
        $keywordIdx = strpos($xml, $keyword);
        if ($keywordIdx === false) return $xml;

        $startParaIdx = strrpos(substr($xml, 0, $keywordIdx), '<w:p');
        $endParaIdx = strpos($xml, '</w:p>', $keywordIdx);
        if ($startParaIdx === false || $endParaIdx === false) return $xml;

        $originalPara = substr($xml, $startParaIdx, ($endParaIdx + 6) - $startParaIdx);
        return str_replace($originalPara, $newParagraphsXml . $originalPara, $xml);
    }
}
