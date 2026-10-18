<?php

namespace App\Services;

use App\Models\Acta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

/**
 * Paquete documental del acta de novedad (fase 2).
 *
 * Genera los documentos a partir del Acta COMPLETA — con todos sus ítems —
 * en lugar del primer ítem como hacía el flujo de una sola asignación:
 *
 *   pdf()    Acta de novedad en PDF (letter, tablero de todos los activos)
 *   docx()   Certificado de responsabilidad en Word (plantilla documentos)
 *   xlsx()   Planilla de novedad en Excel (plantilla, 1 fila por ítem)
 *   zip()    Paquete con los tres archivos + adjunto de autorización
 */
class ActaDocumentService
{
    protected string $formatosDir;
    protected string $tempDir;
    public function __construct()
    {
        $this->formatosDir = storage_path('app/formatos');
        $this->tempDir = storage_path('app');
    }

    /* ==================================================================== */
    /*  PDF — acta de novedad con el consolidado de todos los ítems         */
    /* ==================================================================== */
    public function pdf(Acta $acta): Response
    {
        $datos = $this->datos($acta);

        $html = view('pdf.acta_novedad', $datos)->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->stream($this->nombre($acta, 'pdf'));
    }

    /* ==================================================================== */
    /*  DOCX — certificado de responsabilidad del acta completa             */
    /* ==================================================================== */
    public function docx(Acta $acta): string
    {
        $templatePath = $this->formatosDir . '/responsabilidad.docx';
        if (file_exists($templatePath)) {
            return $this->docxDesdePlantilla($templatePath, $acta);
        }

        // Sin plantilla institucional: generar el documento completo desde cero
        return $this->docxDesdeCero($acta);
    }

    /* ------------------------------------------------------------------ */
    /*  DOCX desde plantilla (formato institucional)                       */
    /* ------------------------------------------------------------------ */
    private function docxDesdePlantilla(string $templatePath, Acta $acta): string
    {
        $tempPath = $this->tempDir . '/temp_acta_resp_' . uniqid() . '.docx';
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

        $fechaStr = $acta->fecha_accion?->format('Y-m-d') ?? now()->format('Y-m-d');
        $dependencia = $acta->usuario_dependencia ?? $acta->usuario_area ?? 'N/A';

        // Datos del responsable en la tabla de datos (dos columnas)
        $xml = $this->docxTabla($xml, 'Nombres y Apellidos', $acta->usuario_nombre ?? '—');
        $xml = $this->docxTabla($xml, 'Tipo y No. Identificación', $acta->usuario_cedula ? 'C.C. ' . $acta->usuario_cedula : '—');
        $xml = $this->docxTabla($xml, 'Dependencia o centro logístico', $dependencia);
        $xml = $this->docxTabla($xml, 'Fecha', $fechaStr);

        // Bloque con todos los ítems del acta
        $parrafos = $this->docxParrafo('ACTA DE NOVEDAD N° ' . $acta->id . ' — ' . $acta->tipo_accion_label, true);
        $parrafos .= $this->docxParrafo('Generada en línea el ' . now()->format('Y-m-d H:i') . ' (' . ($acta->usuario_ciudad ?? 'Ibagué') . ')');
        $parrafos .= $this->docxParrafo('');

        $parrafos .= $this->docxParrafo('INFORMACIÓN DE LOS BIENES ENTREGADOS (' . $acta->items->count() . '):', true);
        $n = 0;
        foreach ($this->filaItems($acta) as $f) {
            $n++;
            $parrafos .= $this->docxParrafo('- Equipo N° ' . $n . ': '
                . ($f['tipo'] !== 'N/A' ? $f['tipo'] : 'Equipo') . ' · '
                . ($f['marca_modelo'] ?: 'N/A') . ' · Serial ' . ($f['serial'] ?: 'N/A') . ' · '
                . 'Placa ' . ($f['placa'] ?: 'N/A') . ' · ' . 'Activo fijo ' . ($f['activo_fijo'] ?: 'N/A'));
            $parrafos .= $this->docxParrafo('  · Características: Procesador ' . $f['procesador'] . ', RAM ' . $f['ram'] . ', Disco ' . $f['disco']
                . ' · Estado: ' . $f['estado'] . ' · Ficha CMDB: ' . $f['estado_uso'] . ' / ' . $f['tipo_propiedad']);
        }
        $parrafos .= $this->docxParrafo('');
        if ($acta->observaciones) {
            $parrafos .= $this->docxParrafo('Observaciones: ' . $acta->observaciones);
        }

        $xml = $this->docxInsertarAntesDeclaro($xml, $parrafos);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tempPath;
    }

    /* ------------------------------------------------------------------ */
    /*  DOCX desde cero (sin plantilla disponible) — paquete documental    */
    /* ------------------------------------------------------------------ */
    private function docxDesdeCero(Acta $acta): string
    {
        $fechaStr = $acta->fecha_accion?->format('d/m/Y') ?? now()->format('d/m/Y');
        $ciudad = $acta->usuario_ciudad ?? 'Ibagué';

        $p = function (string $text, bool $bold = false, int $sz = 20, string $justify = 'left') {
            return '<w:p><w:pPr><w:jc w:val="' . $justify . '" /><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" /><w:sz w:val="' . $sz . '" /><w:szCs w:val="' . $sz . '" />'
                . ($bold ? '<w:b />' : '') . '</w:rPr></w:pPr>'
                . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" /><w:sz w:val="' . $sz . '" /><w:szCs w:val="' . $sz . '" />' . ($bold ? '<w:b />' : '') . '</w:rPr>'
                . '<w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t></w:r></w:p>';
        };

        $xmlDoc = $p('ACTA DE RESPONSABILIDAD DE BIENES', true, '28', 'center');
        $xmlDoc .= $p('Acta de novedad N° ' . str_pad((string) $acta->id, 5, '0', STR_PAD_LEFT) . ' · ' . $acta->tipo_accion_label, false, '20', 'center');
        $xmlDoc .= $p($ciudad . ', ' . $fechaStr, false, '20', 'right');
        $xmlDoc .= $p('');

        $xmlDoc .= $p('DATOS DEL FUNCIONARIO RESPONSABLE', true);
        $rows = [
            ['Nombre', $acta->usuario_nombre],
            ['Documento', $acta->usuario_cedula ? 'C.C. ' . $acta->usuario_cedula : null],
            ['Cargo', $acta->usuario_cargo],
            ['Área / Dependencia', $acta->usuario_dependencia ?? $acta->usuario_area],
            ['Ciudad', $acta->usuario_ciudad],
            ['Empresa', $acta->usuario_empresa_funcionario],
            ['Fuente de recurso', $acta->usuario_fuente_recurso],
        ];
        foreach ($rows as $r) {
            if ($r[1]) $xmlDoc .= $p($r[0] . ': ' . $r[1]);
        }
        $xmlDoc .= $p('');

        $xmlDoc .= $p('INFORMACIÓN DE LOS BIENES ENTREGADOS (' . $acta->items->count() . '):', true);
        $n = 0;
        foreach ($this->filaItems($acta) as $f) {
            $n++;
            $xmlDoc .= $p('- Equipo N° ' . $n . ': ' . $f['tipo'] . ' · ' . $f['marca_modelo'] . ' · Serial ' . $f['serial'] . ' · Placa ' . $f['placa'] . ' · Activo fijo ' . $f['activo_fijo']);
            $xmlDoc .= $p('  · Procesador ' . $f['procesador'] . ', RAM ' . $f['ram'] . ', Disco ' . $f['disco'] . ' · Estado: ' . $f['estado'] . ' · CMDB: ' . $f['estado_uso'] . ' / ' . $f['tipo_propiedad']);
        }
        $xmlDoc .= $p('');
        if ($acta->observaciones) {
            $xmlDoc .= $p('Observaciones: ' . $acta->observaciones);
        }

        $xmlDoc .= $p('El funcionario identificado arriba recibe los bienes descritos para sus funciones, se compromete a responder por su uso, conservación y custodia, y a devolverlos cuando la entidad lo requiera.', false);
        $xmlDoc .= $p('');

        $xmlDoc .= $p('_______________________', false, '20', 'center');
        $xmlDoc .= $p(($acta->entregador_nombre ?? 'TECNOLOGÍA DE INFORMACIÓN'), true, '20', 'center');
        $xmlDoc .= $p(($acta->entregador_cargo ?? 'Analista de Soporte TIC'), false, '20', 'center');
        $xmlDoc .= $p('Quien entrega', false, '20', 'center');
        $xmlDoc .= $p('');
        $xmlDoc .= $p('_______________________', false, '20', 'center');
        $xmlDoc .= $p(($acta->recibe_nombre ?? $acta->usuario_nombre ?? '—'), true, '20', 'center');
        $xmlDoc .= $p(($acta->recibe_cargo ?? $acta->usuario_cargo ?? ''), false, '20', 'center');
        $xmlDoc .= $p('Quien recibe', false, '20', 'center');

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . $xmlDoc
            . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1080" w:right="1080" w:bottom="1080" w:left="1080"/></w:sectPr>'
            . '</w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $tempPath = $this->tempDir . '/temp_acta_resp_' . uniqid() . '.docx';
        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el archivo Word.');
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $tempPath;
    }

    /* ==================================================================== */
    /*  XLSX — planilla de novedad, una fila por ítem                       */
    /* ==================================================================== */
    public function xlsx(Acta $acta): string
    {
        $templatePath = $this->formatosDir . '/novedad.xlsx';
        if (file_exists($templatePath)) {
            return $this->xlsxDesdePlantilla($templatePath, $acta);
        }

        // Sin plantilla: construir la planilla completa desde cero
        return $this->xlsxDesdeCero($acta);
    }

    /* ------------------------------------------------------------------ */
    /*  XLSX desde plantilla                                               */
    /* ------------------------------------------------------------------ */
    private function xlsxDesdePlantilla(string $templatePath, Acta $acta): string
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $ciudad = $acta->usuario_ciudad ?? 'Ibagué';
        $fecha = $acta->fecha_accion?->format('Y/m/d') ?? now()->format('Y/m/d');

        $sheet->setCellValue('E7', $acta->tipo_accion_label);
        $sheet->setCellValue('H7', $ciudad);
        $sheet->setCellValue('L7', $fecha);

        $filas = $this->filaItems($acta);
        $N = count($filas);

        // La plantilla trae una única fila modelo (11); replicar estilos para N-1 más
        if ($N > 1) {
            $sheet->insertNewRowBefore(12, $N - 1);
            $sheet->duplicateStyle($sheet->getStyle('C11:K11'), 'C12:K' . (11 + $N - 1));
        }

        foreach (array_values($filas) as $i => $f) {
            $r = 11 + $i;
            $sheet->setCellValue('C' . $r, $f['activo_fijo']);
            $sheet->setCellValue('D' . $r, $i + 1);
            $sheet->setCellValue('E' . $r, $f['tipo']);
            $sheet->setCellValue('G' . $r, $f['marca']);
            $sheet->setCellValue('H' . $r, $f['modelo']);
            $sheet->setCellValue('I' . $r, $f['serial']);
            $sheet->setCellValue('J' . $r, $f['placa']);
        }

        // Firmas: se desplazan por la inserción de filas (Fila 25 → 24+N, Fila 28 → 27+N)
        $usuarioSistema = \Illuminate\Support\Facades\Auth::user()?->name ?? 'Analista TIC';
        $filaEntrega = 24 + $N;
        $filaRecibe = 27 + $N;

        // El acta siempre registra >entregador = analista TIC; recibe = responsable
        $sheet->setCellValue('C' . $filaEntrega, 'TECNOLOGÍA DE INFORMACIÓN');
        $sheet->setCellValue('E' . $filaEntrega, $acta->entregador_nombre ?? $usuarioSistema);
        $sheet->setCellValue('K' . $filaEntrega, $acta->entregador_cargo ?? 'Analista de Soporte TIC');

        $sheet->setCellValue('C' . $filaRecibe, $acta->recibe_dependencia ?? $acta->usuario_area ?? 'N/A');
        $sheet->setCellValue('E' . $filaRecibe, $acta->recibe_nombre ?? $acta->usuario_nombre ?? '');
        $sheet->setCellValue('K' . $filaRecibe, $acta->recibe_cargo ?? $acta->usuario_cargo ?? 'N/A');

        $tempPath = $this->tempDir . '/temp_acta_nov_' . uniqid() . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return $tempPath;
    }

    /* ------------------------------------------------------------------ */
    /*  XLSX desde cero (sin plantilla disponible)                         */
    /* ------------------------------------------------------------------ */
    private function xlsxDesdeCero(Acta $acta): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Novedad de activos');

        $sheet->setCellValue('A1', 'PLANILLA DE NOVEDADES DE ACTIVOS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Acta N° ' . str_pad((string) $acta->id, 5, '0', STR_PAD_LEFT) . ' · ' . $acta->tipo_accion_label);
        $sheet->setCellValue('A3', ($acta->usuario_ciudad ?? 'Ibagué') . ' / ' . ($acta->fecha_accion?->format('d/m/Y') ?? now()->format('d/m/Y')));

        // Datos del responsable
        $base = 5;
        $datos = [
            ['Funcionario responsable', $acta->usuario_nombre],
            ['Cédula', $acta->usuario_cedula],
            ['Cargo', $acta->usuario_cargo],
            ['Área / Dependencia', $acta->usuario_dependencia ?? $acta->usuario_area],
            ['Ubicación', $acta->ubicacion],
        ];
        foreach ($datos as $i => $d) {
            $sheet->setCellValue('A' . ($base + $i), $d[0]);
            $sheet->setCellValue('B' . ($base + $i), $d[1]);
            $sheet->getStyle('A' . ($base + $i))->getFont()->setBold(true);
        }

        // Tabla de activos (una fila por ítem, con todas sus identificaciones)
        $filaTabla = $base + count($datos) + 2;
        $cabecera = ['#', 'Tipo', 'Consecutivo', 'Nombre/Hostname', 'Marca', 'Modelo', 'Serial', 'Placa', 'Activo fijo', 'Estado', 'Uso (CMDB)', 'Propiedad'];
        foreach ($cabecera as $i => $t) {
            $sheet->setCellValue([$i + 1, $filaTabla], $t);
        }
        $sheet->getStyle('A' . $filaTabla . ':' . $sheet->getHighestColumn() . $filaTabla)->getFont()->setBold(true);

        foreach (array_values($this->filaItems($acta)) as $i => $f) {
            $r = $filaTabla + 1 + $i;
            foreach ([
                $i + 1, $f['tipo'], $f['consecutivo'], $f['nombre'], $f['marca'],
                $f['modelo'], $f['serial'], $f['placa'], $f['activo_fijo'],
                $f['estado'], $f['estado_uso'], $f['tipo_propiedad'],
            ] as $ci => $v) {
                $sheet->setCellValue([$ci + 1, $r], $v);
            }
        }

        // Firmas
        $usuarioSistema = \Illuminate\Support\Facades\Auth::user()?->name ?? 'Analista TIC';
        $firma = $filaTabla + count($this->filaItems($acta)) + 3;
        $sheet->setCellValue('A' . $firma, 'ENTREGÓ — ' . ($acta->entregador_nombre ?? 'TECNOLOGÍA DE INFORMACIÓN') . ' · ' . ($acta->entregador_cargo ?? 'Analista de Soporte TIC'));
        $sheet->getStyle('A' . $firma)->getFont()->setBold(true);
        $sheet->setCellValue('A' . ($firma + 2), 'RECIBIÓ — ' . ($acta->recibe_nombre ?? $acta->usuario_nombre ?? '—') . ' · ' . ($acta->recibe_cargo ?? $acta->usuario_cargo ?? ''));
        $sheet->getStyle('A' . ($firma + 2))->getFont()->setBold(true);

        // Auto-anchos de columnas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempPath = $this->tempDir . '/temp_acta_nov_' . uniqid() . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return $tempPath;
    }

    /* ==================================================================== */
    /*  ZIP — paquete complete + autorización adjunta                       */
    /* ==================================================================== */
    public function zip(Acta $acta): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $base = storage_path('app/paquete_acta_' . $acta->id . '_' . uniqid());
        $zipPath = $base . '.zip';

        // Documentos individuales
        $datosPdf = $this->datos($acta);
        $pdfContenido = Pdf::loadHTML(view('pdf.acta_novedad', $datosPdf)->render())
            ->setPaper('letter', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->output();

        $docxPath = $this->docx($acta);
        $xlsxPath = $this->xlsx($acta);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el paquete ZIP.');
        }

        $zip->addFromString($this->nombre($acta, 'pdf'), $pdfContenido);
        $zip->addFile($docxPath, $this->nombre($acta, 'docx'));
        $zip->addFile($xlsxPath, $this->nombre($acta, 'xlsx'));

        // Autorización adjunta (jefe superior para segundo computador) — disk 'local'
        if ($acta->autorizacion_path) {
            $adjunto = storage_path('app/' . $acta->autorizacion_path);
            if (file_exists($adjunto)) {
                $nombreAdj = 'autorizacion_jefe_superior.' . pathinfo($adjunto, PATHINFO_EXTENSION);
                $zip->addFile($adjunto, $nombreAdj);
            }
        }

        $zip->close();
        @unlink($docxPath);
        @unlink($xlsxPath);

        return response()->download($zipPath, $this->nombre($acta, 'zip'))->deleteFileAfterSend(true);
    }

    /* ==================================================================== */
    /*  Helpers                                                             */
    /* ==================================================================== */

    /** Datos comunes para la vista PDF del acta. */
    private function datos(Acta $acta): array
    {
        $acta->load(['items.equipo.tipoRecurso', 'registradoPor']);

        return [
            'acta'  => $acta,
            'filas' => $this->filaItems($acta),
        ];
    }

    /** Filas normalizadas de todos los ítems del acta (una por equipo). */
    private function filaItems(Acta $acta): array
    {
        return $acta->items->map(function ($item) {
            $e = $item->equipo;
            return [
                'tipo' => $e?->tipoRecurso?->nombre ?? 'N/A',
                'nombre' => $e?->nombre_equipo ?: ($e?->serial ?: 'N/A'),
                'marca' => $e?->marca ?? '',
                'modelo' => $e?->modelo ?? '',
                'marca_modelo' => trim(($e?->marca ?? '') . ' ' . ($e?->modelo ?? '')) ?: 'N/A',
                'serial' => $e?->serial ?? 'N/A',
                'placa' => $e?->placa ?? 'N/A',
                'activo_fijo' => $e?->activo_fijo ?? $e?->placa ?? 'N/A',
                'consecutivo' => $e?->consecutivo ?? 'N/A',
                'procesador' => $e?->procesador ?? 'N/A',
                'ram' => $e?->ram ?? 'N/A',
                'disco' => $e?->disco ?? 'N/A',
                'estado' => \App\Services\Dashboard\DashboardService::estadoLabel($e?->estado_operativo ?? ''),
                'estado_uso' => $e?->estado_uso ?? 'N/A',
                'tipo_propiedad' => $e?->tipo_propiedad ?? 'N/A',
                'administrado' => $e?->administrado ?? 'N/A',
                'observaciones_item' => $e?->observaciones ?? '',
            ];
        })->all();
    }

    private function nombre(Acta $acta, string $ext): string
    {
        $slug = 'acta_novedad_' . str_pad((string) $acta->id, 5, '0', STR_PAD_LEFT)
            . '_' . ($acta->usuario_cedula ?? 'sin_funcionario')
            . '_' . now()->format('Ymd');

        return match ($ext) {
            'pdf'   => $slug . '.pdf',
            'docx'  => 'responsabilidad_' . $slug . '.docx',
            'xlsx'  => 'novedad_' . $slug . '.xlsx',
            'zip'   => 'paquete_' . $slug . '.zip',
            default => $slug . '.' . $ext,
        };
    }

    /* --- Replicación de los helpers DOCX de DocumentGeneratorService ----- */

    private function docxTabla(string $xml, string $key, string $value): string
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

    private function docxParrafo(string $text, bool $isBold = false): string
    {
        $boldXml = $isBold ? '<w:b/>' : '';
        return '<w:p>'
            . '<w:pPr>'
            . '<w:spacing w:line="276" w:lineRule="auto" />'
            . '<w:ind w:right="257" />'
            . '<w:jc w:val="left" />'
            . '<w:rPr>'
            . '<w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" />'
            . '<w:color w:val="000000" />'
            . '<w:sz w:val="20" />'
            . '<w:szCs w:val="20" />'
            . $boldXml
            . '</w:rPr>'
            . '</w:pPr>'
            . '<w:r>'
            . '<w:rPr>'
            . '<w:rFonts w:ascii="Montserrat" w:hAnsi="Montserrat" w:eastAsia="Montserrat" w:cs="Montserrat" />'
            . '<w:color w:val="000000" />'
            . '<w:sz w:val="20" />'
            . '<w:szCs w:val="20" />'
            . $boldXml
            . '</w:rPr>'
            . '<w:t xml:space="preserve">' . htmlspecialchars($text) . '</w:t>'
            . '</w:r>'
            . '</w:p>';
    }

    private function docxInsertarAntesDeclaro(string $xml, string $newParagraphsXml): string
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
