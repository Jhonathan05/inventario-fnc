<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Novedad de Activo - {{ $datos['serial'] ?? '' }}</title>
    <style>
        @page {
            margin: 20px 30px;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        .no-border, .no-border td {
            border: none !important;
        }
        
        /* Header Table */
        .header-table {
            margin-bottom: 20px;
        }
        .header-table td {
            font-weight: bold;
        }
        .logo-cell {
            width: 15%;
            padding: 5px;
        }
        .logo {
            max-width: 80px;
            max-height: 50px;
        }
        .title-cell {
            width: 65%;
            font-size: 12px;
        }
        .meta-cell {
            width: 20%;
            font-size: 9px;
            text-align: left;
            padding-left: 5px;
        }

        /* Novedad Info */
        .info-section {
            margin-bottom: 15px;
        }
        .info-section td {
            text-align: center;
        }
        .info-label {
            font-weight: bold;
            font-size: 10px;
            display: inline-block;
            margin-right: 5px;
        }
        .info-box {
            border: 1px solid #000;
            padding: 4px 15px;
            display: inline-block;
            min-width: 100px;
            text-align: center;
            font-weight: normal;
        }

        /* Main Table */
        .main-table {
            margin-bottom: 30px;
        }
        .main-table th {
            background-color: #d9d9d9;
            font-weight: bold;
            font-size: 9px;
        }
        .main-table td {
            font-size: 9px;
            height: 18px;
        }
        .text-na {
            color: #888;
        }
        .fw-bold {
            font-weight: bold;
        }

        /* Signatures */
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .sig-box {
            border: 1px solid #000;
            text-align: center;
            padding: 5px;
            margin-bottom: 2px;
            font-weight: bold;
            font-size: 9px;
            min-height: 12px;
        }
        .sig-label {
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 10px;
        }
        .sig-col {
            width: 45%;
        }
        .sig-spacer {
            width: 10%;
        }
    </style>
</head>
<body>


    <table class="header-table">
        <tr>
            <td rowspan="2" class="logo-cell">
                @php
                    $pngPath = public_path('imagenes/federacion cafeteros logo.png');
                    $logoBase64 = '';
                    if (file_exists($pngPath)) {
                        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($pngPath));
                    } else {
                        $logoPath = public_path('branding/logo-fnc.svg');
                        if (file_exists($logoPath)) {
                            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
                        }
                    }
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo FNC" style="max-width: 120px; max-height: 70px;">
                @else
                    FNC
                @endif
            </td>
            <td class="title-cell">FEDERACIÓN NACIONAL DE CAFETEROS DE COLOMBIA</td>
            <td class="meta-cell">
                Código: FE-BS-F-0069<br><br>
                Fecha: 11/04/2017
            </td>
        </tr>
        <tr>
            <td class="title-cell">NOVEDAD DE ACTIVO</td>
            <td class="meta-cell">Versión: 1</td>
        </tr>
    </table>


    <table class="no-border info-section">
        <tr>
            <td style="width: 33%;">
                <span class="info-label">Tipo de Novedad</span>
                <div class="info-box" style="color: #666;">Inventario Físico</div>
            </td>
            <td style="width: 33%;">
                <span class="info-label">Ubicación:</span>
                <div class="info-box">{{ $datos['ciudad'] ?? 'Ibagué' }}</div>
            </td>
            <td style="width: 33%;">
                <span class="info-label">Fecha:</span>
                <div class="info-box">{{ date('Y/m/d') }}</div>
                <div style="font-size: 8px; margin-top: 2px; font-weight: bold;">aaaa / mm / dd</div>
            </td>
        </tr>
    </table>


    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 10%;">Activo fijo</th>
                <th style="width: 6%;">SN°</th>
                <th style="width: 22%;">Denominación del activo fijo</th>
                <th style="width: 10%;">Marca</th>
                <th style="width: 12%;">Modelo</th>
                <th style="width: 12%;">Serie</th>
                <th style="width: 10%;">Placa de inventario</th>
                <th style="width: 18%;">Observaciones</th>
            </tr>
        </thead>
        <tbody>

            <tr>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="fw-bold">{{ mb_strtoupper($datos['tipo_recurso'] ?? 'EQUIPO PORTATIL') }}</td>
                <td class="fw-bold">{{ mb_strtoupper($datos['marca'] ?? '—') }}</td>
                <td class="fw-bold">{{ mb_strtoupper($datos['modelo'] ?? '—') }}</td>
                <td class="fw-bold">{{ mb_strtoupper($datos['serial'] ?? '—') }}</td>
                <td class="fw-bold">{{ $datos['activo_fijo'] ?? '—' }}</td>
                <td class="fw-bold">ENTREGA DE EQUIPO</td>
            </tr>
            

            <tr>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="fw-bold">ADAPTADOR CORRIENTE PORTATIL</td>
                <td class="fw-bold">{{ mb_strtoupper($datos['marca'] ?? '—') }}</td>
                <td class="fw-bold">ILEGIBLE</td>
                <td class="fw-bold">ILEGIBLE</td>
                <td class="text-na">N/A</td>
                <td class="fw-bold">ENTREGA DE EQUIPO</td>
            </tr>


            @for ($i = 0; $i < 9; $i++)
            <tr>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
                <td class="text-na">N/A</td>
            </tr>
            @endfor
        </tbody>
    </table>


    <table class="no-border signatures" style="width: 100%; border-spacing: 0 20px;">

        <tr>
            <td style="width: 15%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper(auth()->user()->dependencia ?? 'TI') }}</div>
                <div class="sig-label">DEPENDENCIA</div>
            </td>
            <td style="width: 50%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper(auth()->user()->name ?? '—') }}</div>
                <div class="sig-label">NOMBRE Y FIRMA DE QUIEN ENTREGA</div>
            </td>
            <td style="width: 15%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box"></div>
                <div class="sig-label">Cod. Personal</div>
            </td>
            <td style="width: 20%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper(auth()->user()->cargo ?? 'ANALISTA TIC') }}</div>
                <div class="sig-label">Cargo</div>
            </td>
        </tr>
        

        <tr><td colspan="4" style="height: 10px;"></td></tr>


        <tr>
            <td style="width: 15%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper($datos['seccional'] ?? '—') }}</div>
                <div class="sig-label">DEPENDENCIA</div>
            </td>
            <td style="width: 50%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper($datos['nombre_usuario'] ?? '—') }}</div>
                <div class="sig-label">NOMBRE Y FIRMA DE QUIEN RECIBE</div>
            </td>
            <td style="width: 15%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box"></div>
                <div class="sig-label">Cod. Personal</div>
            </td>
            <td style="width: 20%; padding: 0 5px; vertical-align: top;">
                <div class="sig-box">{{ mb_strtoupper($datos['cargo'] ?? '—') }}</div>
                <div class="sig-label">Cargo</div>
            </td>
        </tr>
    </table>

</body>
</html>
