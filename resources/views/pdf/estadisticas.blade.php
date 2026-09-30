<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas del Inventario TIC</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #1e3a5f;
            margin-bottom: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #b52233;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .date {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .card {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .row {
            width: 100%;
            display: table;
            margin-bottom: 20px;
        }
        .col {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #b52233;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #1e3a5f;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Estadísticas del Inventario TIC</h1>
        <p>Federación Nacional de Cafeteros - Comité Tolima</p>
    </div>

    <div class="date">
        Generado el: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="row">
        <div class="col">
            <div class="card">
                <h2>Resumen General</h2>
                <div class="stat-box" style="margin-bottom: 10px;">
                    <div class="stat-number">{{ $stats['total_equipos'] }}</div>
                    <div>Total Equipos Registrados</div>
                </div>
                <div class="stat-box" style="margin-bottom: 10px;">
                    <div class="stat-number">{{ $stats['total_mantenimientos'] }}</div>
                    <div>Total Mantenimientos Registrados</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">{{ $stats['total_asignaciones'] }}</div>
                    <div>Movimientos / Asignaciones</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <h2>Estado Operativo</h2>
                <table>
                    <tr>
                        <th>Estado</th>
                        <th>Cantidad</th>
                    </tr>
                    <tr>
                        <td>Activos</td>
                        <td>{{ $stats['equipos_activos'] }}</td>
                    </tr>
                    <tr>
                        <td>Inactivos</td>
                        <td>{{ $stats['equipos_inactivos'] }}</td>
                    </tr>
                    <tr>
                        <td>En Mantenimiento</td>
                        <td>{{ $stats['equipos_mantenimiento'] }}</td>
                    </tr>
                    <tr>
                        <td>Dados de Baja</td>
                        <td>{{ $stats['equipos_baja'] }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Equipos por Categoría</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['equipos_por_tipo'] as $tipo)
                <tr>
                    <td>{{ $tipo->nombre }}</td>
                    <td>{{ $tipo->total }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Mantenimientos Recientes</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['mantenimientos_recientes'] as $mant)
                <tr>
                    <td>{{ $mant->fecha_evento?->format('d/m/Y') }}</td>
                    <td>{{ $mant->equipo?->serial ?? 'N/A' }}</td>
                    <td>{{ ucfirst($mant->tipo_evento) }}</td>
                    <td>{{ Str::limit($mant->descripcion, 50) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No hay mantenimientos registrados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Asignaciones / Movimientos Recientes</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['ultimas_asignaciones'] as $asig)
                <tr>
                    <td>{{ $asig->fecha_accion?->format('d/m/Y') }}</td>
                    <td>{{ $asig->equipo?->serial ?? 'N/A' }}</td>
                    <td>{{ $asig->tipo_accion_label }}</td>
                    <td>{{ $asig->usuario_nombre ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No hay movimientos recientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>
