<?php

namespace Database\Seeders;

use App\Mock\MockUsers;
use App\Models\AsignacionResponsabilidad;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\Licencia;
use App\Models\Prestamo;
use App\Models\Ticket;
use App\Models\TipoRecurso;
use App\Services\Auth\IdentityProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración para desarrollo (nunca en producción).
 *
 * Repuebla un parque sintético que ejercita todos los paneles del tablero:
 * funcionarios, equipos en los cinco estados operativos, HelpDesk (abiertos,
 * críticos y cerrados con MTTR), licencias con cupos, préstamos (vigente,
 * vencido y devuelto) y actas de responsabilidad. Las métricas del dashboard
 * salen del esquema real, así que sembrar esto no inventa indicadores.
 *
 * Idempotente por omisión: no hace nada si ya hay equipos registrados.
 *
 * Uso: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Equipo::withTrashed()->count() > 0) {
            $this->command->warn('Ya hay equipos registrados; no se siembran datos de demostración.');

            return;
        }

        $hoy = Carbon::today();

        /** @var IdentityProvisioner $provisioner */
        $provisioner = app(IdentityProvisioner::class);

        $admin = $provisioner->provision(
            MockUsers::PROFILES[0]['email'],
            MockUsers::PROFILES[0]['nombre'],
            MockUsers::PROFILES[0]['role'],
        );

        $analista = $provisioner->provision(
            MockUsers::PROFILES[1]['email'],
            MockUsers::PROFILES[1]['nombre'],
            MockUsers::PROFILES[1]['role'],
        );

        $tipos = TipoRecurso::query()->pluck('id', 'nombre');
        $tipoId = static fn (string $nombre): int => (int) ($tipos[$nombre] ?? $tipos->first());

        DB::transaction(function () use ($hoy, $admin, $analista, $tipoId): void {
            // -----------------------------------------------------------
            // Funcionarios
            // -----------------------------------------------------------
            $funcionarios = [];

            foreach ([
                ['1020345678', 'Laura Camila', 'Ríos Bermúdez', 'Analista Administrativo', 'Gestión Humana', 'Caldas', 'Manizales', 'Planta'],
                ['1032456789', 'Andrés Felipe', 'Ospina Cardona', 'Técnico de Soporte', 'Tecnología', 'Caldas', 'Manizales', 'Contrato término fijo'],
                ['1043567890', 'Diana Marcela', 'Quintero López', 'Coordinadora de Compras', 'Abastecimiento', 'Risaralda', 'Pereira', 'Planta'],
                ['1054678901', 'Julián Esteban', 'Marín Salazar', 'Auxiliar de Inventario', 'Almacén', 'Caldas', 'Manizales', 'Contrato término fijo'],
                ['1065789012', 'Carolina', 'Restrepo Álvarez', 'Profesional Especializado', 'Planeación', 'Antioquia', 'Medellín', 'Prestación de servicios'],
            ] as [$identificacion, $nombres, $apellidos, $cargo, $area, $departamento, $ciudad, $vinculacion]) {
                $funcionarios[] = Funcionario::create([
                    'identificacion' => $identificacion,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'cargo' => $cargo,
                    'area' => $area,
                    'departamento' => $departamento,
                    'ciudad' => $ciudad,
                    'empresa_funcionario' => 'Federación Nacional de Cafeteros',
                    'tipo_vinculacion' => $vinculacion,
                    'estado' => $identificacion === '1065789012' ? 'Inactivo' : 'Activo',
                ]);
            }

            // -----------------------------------------------------------
            // Equipos (12: 4 asignados, 4 disponibles, 2 mantenimiento, 2 baja)
            // -----------------------------------------------------------
            $definicion = [
                // nombre, tipo, marca, modelo, serial, activo fijo, placa, estado, índice responsable, compra, garantía, razón
                ['Portátil Dell Latitude 5420', 'Equipo Portatil', 'Dell', 'Latitude 5420', 'FNC-LAT-0001', 'AF-100241', 'FNC-0001', 'asignado', 0, '2026-03-15', '2026-10-28', null],
                ['Portátil Lenovo ThinkPad T14', 'Equipo Portatil', 'Lenovo', 'ThinkPad T14 Gen 3', 'FNC-LAT-0002', 'AF-100242', 'FNC-0002', 'asignado', 1, '2025-11-20', '2026-11-05', null],
                ['Portátil HP ProBook 450 G9', 'Equipo Portatil', 'HP', 'ProBook 450 G9', 'FNC-LAT-0003', 'AF-100243', 'FNC-0003', 'asignado', 2, '2025-05-10', '2027-05-10', null],
                ['Escritorio Dell OptiPlex 7090', 'Equipo Escritorio', 'Dell', 'OptiPlex 7090', 'FNC-ESC-0001', 'AF-100244', 'FNC-0004', 'asignado', 3, '2024-12-05', '2027-12-05', null],
                ['Escritorio HP EliteDesk 800 G6', 'Equipo Escritorio', 'HP', 'EliteDesk 800 G6', 'FNC-ESC-0002', 'AF-100245', 'FNC-0005', 'disponible', null, '2023-08-18', '2026-08-18', null],
                ['Escritorio Lenovo ThinkCentre M70q', 'Equipo Escritorio', 'Lenovo', 'ThinkCentre M70q', 'FNC-ESC-0003', 'AF-100246', 'FNC-0006', 'disponible', null, '2022-11-30', '2025-11-30', null],
                ['Tableta Samsung Galaxy Tab A8', 'Tableta', 'Samsung', 'Galaxy Tab A8', 'FNC-TAB-0001', 'AF-100247', 'FNC-0007', 'disponible', null, '2026-01-10', '2028-01-10', null],
                ['Impresora HP LaserJet Pro M404dn', 'Impresora', 'HP', 'LaserJet Pro M404dn', 'FNC-IMP-0001', 'AF-100248', 'FNC-0008', 'disponible', null, '2021-06-22', null, null],
                ['Impresora Epson EcoTank L6270', 'Impresora', 'Epson', 'EcoTank L6270', 'FNC-IMP-0002', 'AF-100249', 'FNC-0009', 'mantenimiento', null, '2020-02-14', null, 'Atasco de papel recurrente; revisión de rodillos y cabezales.'],
                ['Escáner Canon imageFORMULA DR-C230', 'Escaner', 'Canon', 'imageFORMULA DR-C230', 'FNC-ESCAN-0001', 'AF-100250', 'FNC-0010', 'mantenimiento', null, '2024-02-09', '2027-02-09', 'Calibración de sensores y limpieza interna programada.'],
                ['Todo en Uno Lenovo IdeaCentre AIO 3', 'Equipo Todo En Uno', 'Lenovo', 'IdeaCentre AIO 3', 'FNC-AIO-0001', 'AF-100251', 'FNC-0011', 'baja', null, null, null, 'Placa principal averiada; reparación no viable económicamente.'],
                ['Portátil Dell Vostro 3510', 'Equipo Portatil', 'Dell', 'Vostro 3510', 'FNC-LAT-0004', 'AF-100252', 'FNC-0012', 'baja', null, null, null, 'Daño por líquidos; equipo fuera de vida útil.'],
            ];

            $equipos = [];

            foreach ($definicion as [$nombre, $tipo, $marca, $modelo, $serial, $activoFijo, $placa, $estado, $indiceResponsable, $compra, $garantia, $razon]) {
                $responsable = $indiceResponsable !== null ? $funcionarios[$indiceResponsable] : null;

                $equipos[] = Equipo::create([
                    'tipo_recurso_id' => $tipoId($tipo),
                    'nombre_equipo' => $nombre,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'serial' => $serial,
                    'activo_fijo' => $activoFijo,
                    'placa' => $placa,
                    'estado_operativo' => $estado,
                    'razon_estado' => $razon,
                    'procesador' => str_contains($tipo, 'Portatil') ? 'Intel Core i5-1135G7' : 'Intel Core i5-10500T',
                    'ram' => '16 GB DDR4',
                    'disco' => 'SSD 512 GB NVMe',
                    'sistema_operativo' => 'Windows 11 Pro 23H2',
                    'fecha_compra' => $compra,
                    'fin_garantia' => $garantia,
                    'tiempo_uso' => $compra !== null ? Carbon::parse($compra)->diffForHumans($hoy, true) : null,
                    'responsable_cedula' => $responsable?->identificacion,
                    'responsable_nombre' => $responsable?->nombre_completo,
                    'responsable_cargo' => $responsable?->cargo,
                    'responsable_ciudad' => $responsable?->ciudad,
                    'responsable_area' => $responsable?->area,
                    'responsable_tipo_recurso' => $responsable !== null ? $tipo : null,
                    'fecha_inicio_responsable' => $responsable !== null ? $hoy->copy()->subMonths(6)->toDateString() : null,
                ]);
            }

            // -----------------------------------------------------------
            // Tickets: mezcla de abiertos y cerrados en 6 meses
            // -----------------------------------------------------------
            $tickets = [
                ['No enciende el equipo de escritorio', 'Hardware', 'Crítica', 'Abierto', 3, 3, $analista, $hoy->copy()->subDays(2), null, null],
                ['Correo corporativo no sincroniza', 'Software', 'Alta', 'En Proceso', 2, 2, $analista, $hoy->copy()->subDays(5), null, null],
                ['Impresora con atascos frecuentes', 'Hardware', 'Media', 'Abierto', 0, 8, null, $hoy->copy()->subDays(9), null, null],
                ['Solicitud de instalación de Office', 'Software', 'Baja', 'Resuelto', 1, 1, $analista, $hoy->copy()->subMonths(2)->setDay(12), $hoy->copy()->subMonths(2)->setDay(13), null],
                ['Tableta con batería degradada', 'Hardware', 'Media', 'Cerrado', 4, 6, $analista, $hoy->copy()->subMonths(4)->setDay(20), $hoy->copy()->subMonths(4)->setDay(22), $hoy->copy()->subMonths(4)->setDay(23)],
                ['Error de red en el área de Almacén', 'Red', 'Alta', 'Cerrado', 3, null, $analista, $hoy->copy()->subMonths(5)->setDay(18), $hoy->copy()->subMonths(5)->setDay(19), $hoy->copy()->subMonths(5)->setDay(20)],
                ['Actualización de antivirus corporativo', 'Software', 'Media', 'Cerrado', 1, 4, null, $hoy->copy()->subMonths(3)->setDay(7), $hoy->copy()->subMonths(3)->setDay(8), null],
                ['Mouse y teclado sin respuesta', 'Hardware', 'Baja', 'Cerrado', 0, 0, $analista, $hoy->copy()->subMonths(1)->setDay(14), $hoy->copy()->subMonths(1)->setDay(15), null],
            ];

            foreach ($tickets as [$titulo, $tipo, $prioridad, $estado, $indiceFuncionario, $indiceEquipo, $responsable, $solicitud, $solucion, $cierre]) {
                Ticket::create([
                    'titulo' => $titulo,
                    'tipo' => $tipo,
                    'prioridad' => $prioridad,
                    'descripcion' => $titulo.'. Reportado por el funcionario y atendido por la mesa de ayuda del Comité.',
                    'estado' => $estado,
                    'funcionario_id' => $funcionarios[$indiceFuncionario]->id,
                    'equipo_id' => $indiceEquipo !== null ? $equipos[$indiceEquipo]->id : null,
                    'user_id' => $responsable?->id,
                    'fecha_solicitud' => $solicitud,
                    'diagnostico_inicial' => $solucion !== null ? 'Se validó el caso en sitio y se documentó la causa probable.' : null,
                    'fecha_diagnostico' => $solucion,
                    'solucion_aplicada' => $solucion !== null ? 'Se aplicó el procedimiento estándar de soporte.' : null,
                    'fecha_solucion' => $solucion,
                    'fecha_cierre' => $cierre,
                    'tiempo_invertido' => $solucion !== null ? '2 horas' : null,
                ]);
            }

            // -----------------------------------------------------------
            // Licencias y cupos asignados
            // -----------------------------------------------------------
            $licencias = [
                Licencia::create([
                    'nombre' => 'Microsoft 365 Business Premium',
                    'tipo_licencia' => 'Suscripción',
                    'cantidad_maxima' => 25,
                    'fecha_inicio' => $hoy->copy()->subYear()->setDay(15),
                    'fecha_vencimiento' => $hoy->copy()->addMonths(1)->setDay(14),
                    'estado' => 'Activa',
                    'correo_compra' => 'compras.tic@example.test',
                    'observaciones' => 'Renovación anual corporativa.',
                ]),
                Licencia::create([
                    'nombre' => 'Autodesk AutoCAD 2026',
                    'tipo_licencia' => 'Suscripción',
                    'cantidad_maxima' => 5,
                    'fecha_inicio' => $hoy->copy()->subYear()->setDay(3),
                    'fecha_vencimiento' => $hoy->copy()->addDays(18),
                    'estado' => 'Activa',
                    'observaciones' => 'Cupos para el área de infraestructura.',
                ]),
                Licencia::create([
                    'nombre' => 'Adobe Creative Cloud',
                    'tipo_licencia' => 'Suscripción',
                    'cantidad_maxima' => 3,
                    'fecha_inicio' => $hoy->copy()->subMonths(14),
                    'fecha_vencimiento' => $hoy->copy()->subMonths(2),
                    'estado' => 'Vencida',
                    'observaciones' => 'Pendiente de decisión de renovación.',
                ]),
                Licencia::create([
                    'nombre' => 'Windows Server 2019 Standard',
                    'tipo_licencia' => 'Vitalicia',
                    'cantidad_maxima' => 2,
                    'fecha_inicio' => $hoy->copy()->subYears(4),
                    'fecha_vencimiento' => null,
                    'estado' => 'Activa',
                    'observaciones' => 'Licencia perpetua del servidor de archivos.',
                ]),
            ];

            $cupos = [
                [0, [0, 1, 2, 3], [0, 1, 2, 3], 'Activa'],
                [1, [0, 1, 2, 4], [0, 1, 2, 3], 'Activa'],
                [2, [0, 1, 2], [0, 1, 4], 'Vencida'],
                [3, [5], [3], 'Activa'],
            ];

            foreach ($cupos as [$indiceLicencia, $indicesEquipo, $indicesFuncionario, $estado]) {
                foreach ($indicesEquipo as $posicion => $indiceEquipo) {
                    DB::table('licencia_asignaciones')->insert([
                        'licencia_id' => $licencias[$indiceLicencia]->id,
                        'equipo_id' => $equipos[$indiceEquipo]->id,
                        'funcionario_id' => $funcionarios[$indicesFuncionario[$posicion]]->id,
                        'fecha_asignacion' => $hoy->copy()->subMonths(4)->toDateString(),
                        'fecha_vencimiento' => null,
                        'estado' => $estado,
                        'observaciones' => null,
                        'created_by' => $admin->id,
                        'updated_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // -----------------------------------------------------------
            // Préstamos: uno vigente por devolver, uno vencido y uno devuelto
            // -----------------------------------------------------------
            Prestamo::create([
                'equipo_id' => $equipos[4]->id,
                'persona_nombre' => 'Sandra Milena Torres',
                'persona_documento' => '1010567890',
                'user_id' => $admin->id,
                'fecha_inicio' => $hoy->copy()->subDays(3),
                'fecha_devolucion_prevista' => $hoy->copy()->addDays(5),
                'duracion' => '8 días',
                'estado' => 'Activo',
                'motivo' => 'Apoyo a la Feria del Café 2026.',
            ]);

            Prestamo::create([
                'equipo_id' => $equipos[6]->id,
                'persona_nombre' => 'Óscar Iván Zapata',
                'persona_documento' => '1015678901',
                'user_id' => $admin->id,
                'fecha_inicio' => $hoy->copy()->subDays(18),
                'fecha_devolucion_prevista' => $hoy->copy()->subDays(4),
                'duracion' => '14 días',
                'estado' => 'Vencido',
                'motivo' => 'Levantamiento de inventario en bodega.',
                'observaciones' => 'Se contactó al funcionario para gestionar la devolución.',
            ]);

            Prestamo::create([
                'equipo_id' => $equipos[7]->id,
                'persona_nombre' => 'Paula Andrea Gómez',
                'persona_documento' => '1026789012',
                'user_id' => $admin->id,
                'fecha_inicio' => $hoy->copy()->subDays(20),
                'fecha_devolucion_prevista' => $hoy->copy()->subDays(10),
                'fecha_devolucion_real' => $hoy->copy()->subDays(9),
                'duracion' => '11 días',
                'estado' => 'Devuelto',
                'motivo' => 'Impresión de material para comité regional.',
                'estado_fisico_devolucion' => 'Buen estado',
            ]);

            // -----------------------------------------------------------
            // Actas de responsabilidad vigentes
            // -----------------------------------------------------------
            $actas = [
                [0, 0, $hoy->copy()->subMonths(8), $hoy->copy()->subDays(11)],
                [1, 1, $hoy->copy()->subMonths(4), $hoy->copy()->addDays(9)],
                [3, 3, $hoy->copy()->subMonths(9), $hoy->copy()->addMonths(3)],
            ];

            foreach ($actas as [$indiceEquipo, $indiceFuncionario, $inicio, $finalEstimada]) {
                $funcionario = $funcionarios[$indiceFuncionario];

                AsignacionResponsabilidad::create([
                    'equipo_id' => $equipos[$indiceEquipo]->id,
                    'user_id' => $admin->id,
                    'nombre_usuario' => $funcionario->nombre_completo,
                    'documento' => $funcionario->identificacion,
                    'empresa' => 'Federación Nacional de Cafeteros',
                    'cargo' => $funcionario->cargo,
                    'area' => $funcionario->area,
                    'correo' => 'usuario'.($indiceFuncionario + 1).'@example.test',
                    'telefono' => '30012345'.str_pad((string) $indiceFuncionario, 2, '0', STR_PAD_LEFT),
                    'tipo_usuario' => 'Funcionario',
                    'fecha_inicio' => $inicio,
                    'fecha_final_estimada' => $finalEstimada,
                    'estado' => 'activa',
                    'observaciones' => 'Acta de responsabilidad vigente.',
                ]);
            }
        });

        $this->command->info(sprintf(
            'Datos de demostración creados: %d funcionarios, %d equipos, %d tickets, %d licencias, %d préstamos, %d actas.',
            Funcionario::count(),
            Equipo::count(),
            Ticket::count(),
            Licencia::count(),
            Prestamo::count(),
            AsignacionResponsabilidad::count(),
        ));
    }
}
