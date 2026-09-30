<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPrestamosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestamos:check-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa préstamos activos y los marca como vencidos si pasó su fecha de devolución, sin desasignar el equipo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $prestamosVencidos = \App\Models\Prestamo::whereIn('estado', ['Pendiente', 'Activo'])
            ->where('fecha_devolucion_prevista', '<', $now)
            ->get();
            
        $count = 0;
        foreach ($prestamosVencidos as $prestamo) {
            $prestamo->estado = 'Vencido';
            $prestamo->save();
            
            // Generar historial
            \App\Models\HistorialAdministrativo::create([
                'equipo_id' => $prestamo->equipo_id,
                'user_id' => $prestamo->user_id, // El sistema / usuario que registró originalmente, o un ID sistema si existiera
                'tipo_cambio' => 'prestamo_vencido',
                'campo_modificado' => 'estado_prestamo',
                'valor_anterior' => 'Activo',
                'valor_nuevo' => 'Vencido',
                'descripcion' => "El préstamo asignado a {$prestamo->persona_nombre} ha alcanzado su fecha de vencimiento prevista.",
            ]);
            $count++;
        }
        
        $this->info("Proceso finalizado. $count préstamos marcados como vencidos.");
    }
}
