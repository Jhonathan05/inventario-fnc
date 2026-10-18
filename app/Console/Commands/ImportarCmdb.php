<?php

namespace App\Console\Commands;

use App\Models\Acta;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\TipoRecurso;
use App\Services\ActaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa el CMDB completo (Microinformática + Listado funcionarios) y crea
 * las asignaciones que vinculan cada equipo a su funcionario.
 *
 * El formato de entrada es el mismo Excel del CMDB:
 *   - Hoja "Microinformatica": 51 columnas (custodia 1-14, activo 21-41)
 *   - Hoja "Listado funcionarios": maestro de funcionarios
 *
 * Uso: php artisan cmdb:importar {archivo.xlsx}
 */
class ImportarCmdb extends Command
{
    protected $signature = 'cmdb:importar {archivo : Ruta al archivo Excel del CMDB} {--dry-run : Mostrar qué se haría sin escribir}';

    protected $description = 'Importa el CMDB completo: equipos, funcionarios y sus asignaciones';

    private int $equiposCreados = 0;
    private int $equiposActualizados = 0;
    private int $asignacionesCreadas = 0;
    private int $funcionariosVinculados = 0;
    private int $omitidos = 0;

    public function handle(): int
    {
        $ruta = $this->argument('archivo');
        $dryRun = $this->bin(false !== $this->option('dry-run'));

        if (! file_exists($ruta)) {
            $this->error("Archivo no encontrado: {$ruta}");
            return 1;
        }

        $this->info("Leyendo CMDB: {$ruta}");
        $spreadsheet = IOFactory::load($ruta);
        $hoja = $spreadsheet->getSheetByName('Microinformatica');

        if (! $hoja) {
            $this->error('No se encontró la hoja "Microinformatica".');
            return 1;
        }

        $filaInicio = 2; // índice 0 = secciones, índice 1 = cabeceras, índice 2+ = datos
        $filas = $hoja->toArray(null, true, true, false);

        $this->info('Procesando ' . (count($filas) - $filaInicio) . ' filas…');

        $tiposCache = [];
        $service = app(ActaService::class);
        $adminUser = \App\Models\User::first();

        foreach (array_slice($filas, $filaInicio) as $indice => $fila) {
            // Columnas (0-indexed):
            // 0=empresa_propietario, 1=dependencia, 2=fuente_recurso, 3=empresa_funcionario,
            // 4=empleado/contratista, 5=cedula, 6=shortname, 7=nombres, 8=departamento,
            // 9=ciudad, 10=cargo, 11=area, 12=ubicacion_piso, 13=tipo_recurso(usuario),
            // 20=tipo, 21=serial, 22=placa, 23=marca, 24=modelo, 25=estado_uso,
            // 26=nombre_equipo, 27=estado_operativo, 28=razon_estado,
            // 29=administrado, 30=procesador, 31=ram, 32=disco, 33=so,
            // 34=fecha_compra, 35=fin_garantia, 36=tiempo_uso, 37=tipo_propiedad,
            // 38=checklist_responsable, 39=orden_remision, 40=observaciones

            $serial = trim((string) ($fila[21] ?? ''));
            $placa = trim((string) ($fila[22] ?? ''));

            if ($serial === '' && $placa === '') {
                $this->omitidos++;
                continue;
            }

            $nombreTipo = trim((string) ($fila[20] ?? ''));
            $tipoRecurso = $tiposCache[$nombreTipo] ?? null;
            if (! $tipoRecurso && $nombreTipo !== '') {
                $tipoRecurso = TipoRecurso::firstOrCreate(['nombre' => $nombreTipo]);
                $tiposCache[$nombreTipo] = $tipoRecurso;
            }

            if (! $tipoRecurso) {
                $this->warn("  Fila " . ($indice + $filaInicio + 1) . ": tipo de recurso '{$nombreTipo}' no encontrado, omitida.");
                $this->omitidos++;
                continue;
            }

            $datosEquipo = [
                'tipo_recurso_id' => $tipoRecurso->id,
                'serial' => $serial ?: 'SIN_SERIAL_' . uniqid(),
                'placa' => $placa ?: null,
                'marca' => trim((string) ($fila[23] ?? '')) ?: null,
                'modelo' => trim((string) ($fila[24] ?? '')) ?: null,
                'nombre_equipo' => trim((string) ($fila[26] ?? '')) ?: null,
                'estado_operativo' => $this->mapearEstado(trim((string) ($fila[27] ?? ''))),
                'razon_estado' => trim((string) ($fila[28] ?? '')) ?: null,
                'procesador' => trim((string) ($fila[30] ?? '')) ?: null,
                'ram' => trim((string) ($fila[31] ?? '')) ?: null,
                'disco' => trim((string) ($fila[32] ?? '')) ?: null,
                'sistema_operativo' => trim((string) ($fila[33] ?? '')) ?: null,
                'estado_uso' => trim((string) ($fila[25] ?? '')) ?: null,
                'administrado' => trim((string) ($fila[29] ?? '')) ?: null,
                'tipo_propiedad' => trim((string) ($fila[37] ?? '')) ?: null,
                'checklist_responsable' => trim((string) ($fila[38] ?? '')) ?: null,
                'orden_remision' => trim((string) ($fila[39] ?? '')) ?: null,
                'observaciones' => trim((string) ($fila[40] ?? '')) ?: null,
            ];

            $fechaCompra = trim((string) ($fila[34] ?? ''));
            if ($fechaCompra !== '' && is_numeric($fechaCompra)) {
                $datosEquipo['fecha_compra'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $fechaCompra)->format('Y-m-d');
            }

            $finGarantia = trim((string) ($fila[35] ?? ''));
            if ($finGarantia !== '' && is_numeric($finGarantia)) {
                $datosEquipo['fin_garantia'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $finGarantia)->format('Y-m-d');
            }

            $tiempoUso = trim((string) ($fila[36] ?? ''));
            if ($tiempoUso !== '' && is_numeric($tiempoUso)) {
                $datosEquipo['tiempo_uso'] = round((float) $tiempoUso, 1);
            }

            // Buscar equipo existente por serial o placa
            $equipo = Equipo::where('serial', $datosEquipo['serial'])
                ->orWhere('placa', $datosEquipo['placa'] ?? '___')
                ->first();

            if ($equipo) {
                if (! $dryRun) {
                    $equipo->fill($datosEquipo)->save();
                }
                $this->equiposActualizados++;
            } else {
                if (! $dryRun) {
                    $equipo = Equipo::create($datosEquipo);
                }
                $this->equiposCreados++;
            }

            // ---- Crear asignación si hay funcionario ----
            $cedula = $this->normalizarCedula((string) ($fila[5] ?? ''));
            $nombreFuncionario = trim((string) ($fila[7] ?? ''));

            // Debug: rastrear la fila de NATALIA (serial H2NT3J4)
            if ($datosEquipo['serial'] === 'H2NT3J4') {
            }

            if ($cedula !== '' && $nombreFuncionario !== '' && $equipo && $datosEquipo['estado_operativo'] === 'en_operacion') {
                $this->crearAsignacionDesdeCmdb($equipo, $fila, $cedula, $service, $adminUser, $dryRun);
            }
        }

        $this->newLine();
        $this->info("RESUMEN:");
        $this->table(['Métrica', 'Cantidad'], [
            ['Equipos creados', $this->equiposCreados],
            ['Equipos actualizados', $this->equiposActualizados],
            ['Asignaciones creadas', $this->asignacionesCreadas],
            ['Funcionarios vinculados', $this->funcionariosVinculados],
            ['Filas omitidas', $this->omitidos],
        ]);

        return 0;
    }

    private function crearAsignacionDesdeCmdb(Equipo $equipo, array $fila, string $cedula, ActaService $service, $user, bool $dryRun): void
    {
        // Verificar si ya tiene una asignación
        $yaAsignado = $equipo->asignaciones()
            ->where('usuario_cedula', $cedula)
            ->where('tipo_accion', 'inventario_fisico')
            ->exists();

        if ($yaAsignado) {
            return;
        }

        $snapshot = [
            'usuario_cedula' => $cedula,
            'usuario_nombre' => trim((string) ($fila[7] ?? '')),
            'usuario_cargo' => trim((string) ($fila[10] ?? '')),
            'usuario_area' => trim((string) ($fila[11] ?? '')),
            'usuario_ciudad' => trim((string) ($fila[9] ?? '')),
            'usuario_departamento' => trim((string) ($fila[8] ?? '')),
            'usuario_empresa_propietaria' => trim((string) ($fila[0] ?? '')),
            'usuario_empresa_funcionario' => trim((string) ($fila[3] ?? '')),
            'usuario_tipo_vinculacion' => trim((string) ($fila[4] ?? '')),
            'usuario_shortname' => trim((string) ($fila[6] ?? '')),
            'usuario_fuente_recurso' => trim((string) ($fila[2] ?? '')),
            'usuario_piso' => trim((string) ($fila[12] ?? '')),
            'usuario_seccional' => trim((string) ($fila[1] ?? '')),
            'usuario_distrito' => trim((string) ($fila[11] ?? '')),
        ];

        if (! $dryRun) {
            try {
                // Crear la asignación directamente (sin acta, es una importación)
                $asignacion = $equipo->asignaciones()->create([
                    'tipo_accion' => 'inventario_fisico',
                    'fecha_accion' => now(),
                    'motivo' => 'Importado del CMDB',
                    'user_id' => $user?->id,
                ] + $snapshot);

                // Actualizar el bloque responsable en el equipo
                $equipo->update([
                    'responsable_cedula' => $cedula,
                    'responsable_nombre' => $snapshot['usuario_nombre'],
                    'responsable_cargo' => $snapshot['usuario_cargo'],
                    'responsable_ciudad' => $snapshot['usuario_ciudad'],
                    'responsable_area' => $snapshot['usuario_area'],
                ]);

                $this->asignacionesCreadas++;
                $this->funcionariosVinculados++;
            } catch (\Exception $e) {
                $this->warn("  No se pudo crear asignación para {$equipo->serial}: " . $e->getMessage());
            }
        } else {
            $this->asignacionesCreadas++;
        }
    }

    /**
     * Normaliza la cédula: Excel guarda números grandes en notación
     * científica (1.075310355E9) y hay que convertirlos a entero legible.
     */
    private function normalizarCedula(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        // Quitar puntos y comas de miles
        $valor = preg_replace('/[.,](?=\d{3}\b)/', '', $valor);

        // Si es numérico, convertir a entero sin notación científica
        if (is_numeric($valor)) {
            return (string) (int) (float) $valor;
        }

        return $valor;
    }

    private function mapearEstado(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        return match (true) {
            str_contains($texto, 'operaci'), str_contains($texto, 'activo') => 'en_operacion',
            str_contains($texto, 'disponible'), str_contains($texto, 'almacenado') => 'disponible',
            str_contains($texto, 'mantenimiento'), str_contains($texto, 'alistamiento'), str_contains($texto, 'reparacion') => 'reparacion',
            str_contains($texto, 'baja'), str_contains($texto, 'obsoleto') => 'baja',
            str_contains($texto, 'prestamo'), str_contains($texto, 'temporal') => 'prestamo',
            default => 'en_operacion',
        };
    }

    private function bin(bool $v): bool
    {
        return $v;
    }
}
