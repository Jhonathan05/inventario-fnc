{{-- Acta de novedad de activos (PDF) — consolida TODOS los ítems del acta --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de novedad N° {{ $acta->id }}</title>
    <style>
        @page { margin: 26px 34px; font-family: Arial, sans-serif; font-size: 10px; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #14181f; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #444; padding: 4px 6px; vertical-align: middle; }
        .no-border td { border: none !important; }
        th { background: #efe3e4; font-weight: bold; font-size: 9px; text-align: center; }
        .titulo { font-size: 15px; font-weight: bold; text-align: center; }
        .subtitulo { text-align: center; font-size: 10px; color: #333; }
        .seccion { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
        .kv td.label { background: #f7f7f9; font-weight: bold; width: 22%; }
        .firmas td { border: none; }
        .linea { border-top: 1px solid #000; margin-top: 40px; }
        .firmas-nombre { font-size: 9px; text-align: center; }
        .fila-cc { font-size: 8px; text-align: center; color: #333; }
        .ficha { font-size: 8px; color: #333; }
    </style>
</head>
<body>

    <div class="titulo">ACTA DE NOVEDAD DE ASIGNACIÓN / DEVOLUCIÓN DE BIENES</div>
    <div class="subtitulo">Número {{ str_pad($acta->id, 5, '0', STR_PAD_LEFT) }} · {{ strtoupper($acta->tipo_accion_label) }}</div>

    <p style="text-align:right;font-size:9px">
        {{ strtoupper($acta->usuario_ciudad ?? 'IBAGUÉ') }} / {{ $acta->fecha_accion?->format('d/m/Y') ?? now()->format('d/m/Y') }}
        @if ($acta->fecha_inicio) &nbsp;·&nbsp; INICIO: {{ $acta->fecha_inicio->format('d/m/Y') }} @endif
        @if ($acta->fecha_fin_probable) &nbsp;·&nbsp; FIN APROX: {{ $acta->fecha_fin_probable->format('d/m/Y') }} @endif
    </p>

    {{-- ============================ RESPONSALE ============================ --}}
    <p class="seccion">RESPONSABLE DEL BIEN</p>
    <table class="kv no-border">
        <tr>
            <td class="label">Nombre del responsable</td><td>{{ $acta->usuario_nombre ?? '—' }}</td>
            <td class="label">Documento de identidad</td><td>{{ $acta->usuario_cedula ? 'C.C. ' . $acta->usuario_cedula : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cargo</td><td>{{ $acta->usuario_cargo ?? '—' }}</td>
            <td class="label">Área / Dependencia</td><td>{{ $acta->usuario_dependencia ?? $acta->usuario_area ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Ciudad / Ubicación</td><td>{{ $acta->usuario_ciudad ?? '—' }} {{ $acta->usuario_piso ? '· Piso ' . $acta->usuario_piso : '' }}</td>
            <td class="label">Empresa / Fuente</td><td>{{ $acta->usuario_empresa_funcionario ?? '—' }} · {{ $acta->usuario_fuente_recurso ?? '—' }} · {{ $acta->usuario_tipo_vinculacion ?? '—' }}</td>
        </tr>
    </table>

    {{-- ============================ START ACTIVOS ============================ --}}
    <p class="seccion" style="margin-top:12px">INFORMACIÓN DE LOS BIENES ENTREGADOS ({{ count($filas) }})</p>
    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:14%">Tipo</th>
                <th style="width:18%">Nombre / Hostname</th>
                <th style="width:12%">Marca / Modelo</th>
                <th style="width:14%">Serial</th>
                <th style="width:10%">Placa inv.</th>
                <th style="width:12%">Activo fijo</th>
                <th style="width:16%">Ficha CMDB</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $i => $f)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $f['tipo'] }}</td>
                    <td>
                        {{ $f['nombre'] }}
                        <div class="ficha">Consecutivo {{ $f['consecutivo'] }} · {{ $f['estado'] }}</div>
                    </td>
                    <td>{{ $f['marca_modelo'] }}</td>
                    <td>{{ $f['serial'] }}</td>
                    <td>{{ $f['placa'] }}</td>
                    <td>{{ $f['activo_fijo'] }}</td>
                    <td>
                        <span class="ficha">Uso: {{ $f['estado_uso'] }}</span><br>
                        <span class="ficha">Propiedad: {{ $f['tipo_propiedad'] }}</span>
                        @if (in_array($f['administrado'], ['ADMINISTRADO', 'CONTROLADO']))
                            <br><span class="ficha">Adm: {{ $f['administrado'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($acta->observaciones)
        <p class="seccion" style="margin-top:10px">OBSERVACIONES</p>
        <p style="margin-top:0">{{ $acta->observaciones }}</p>
    @endif

    @if ($acta->autorizacion_path)
        <p style="font-size:9px">⚠ Este acta cuenta con autorización adjunta del jefe superior (segundo computador).</p>
    @endif

    <p style="font-size:9px;margin-top:8px">
        Entregada por <b>{{ $acta->entregador_nombre ?? 'TECNOLOGÍA DE INFORMACIÓN' }}</b>
        {{ $acta->entregador_cargo ? '(' . $acta->entregador_cargo . ')' : '' }}
        @if ($acta->registradoPor) · Registrada por <b>{{ $acta->registradoPor->name }}</b> @endif
    </p>

    {{-- ============================ FIRMAS ============================ --}}
    <table class="firmas">
        <tr>
            <td style="width:50%;text-align:center">
                <div class="linea"></div>
                <div class="firmas-nombre" style="margin-top:3px">
                    <b>{{ $acta->entregador_nombre ?? 'TECNOLOGÍA DE INFORMACIÓN' }}</b><br>
                    {{ $acta->entregador_cargo ?? 'Analista de Soporte TIC' }}
                    @if ($acta->entregador_codigo)<br class="fila-cc"> C.C. {{ $acta->entregador_codigo }} @endif
                </div>
                <div style="font-size:9px"><b>Quien entrega</b></div>
                <div class="fila-cc">{{ $acta->entregador_dependencia ?? 'Tecnología de Información' }}</div>
            </td>
            <td style="width:50%;text-align:center">
                <div class="linea"></div>
                <div class="firmas-nombre" style="margin-top:3px">
                    <b>{{ $acta->recibe_nombre ?? $acta->usuario_nombre ?? '—' }}</b><br>
                    {{ $acta->recibe_cargo ?? $acta->usuario_cargo ?? '' }}
                    @if ($acta->recibe_codigo ?? $acta->usuario_cedula)<br class="fila-cc"> C.C. {{ $acta->recibe_codigo ?? $acta->usuario_cedula }} @endif
                </div>
                <div style="font-size:9px"><b>Quien recibe</b></div>
                <div class="fila-cc">{{ $acta->recibe_dependencia ?? $acta->usuario_dependencia ?? $acta->usuario_area ?? '—' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
