<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class CrearBackup extends Command
{
    protected $signature = 'backups:crear';

    protected $description = 'Crea un backup comprimido (gzip) de la base de datos del inventario y aplica la retención configurada.';

    public function handle(BackupService $backups): int
    {
        $this->info('Creando backup…');

        try {
            $backup = $backups->crear();
        } catch (Throwable $e) {
            $this->error('El backup falló: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->table(
            ['Backup', 'Tamaño', 'Tablas', 'Duración (s)', 'Retención'],
            [[
                $backup['nombre'],
                number_format($backup['bytes'], 0, ',', '.') . ' B',
                $backup['tablas'],
                $backup['duracion'],
                $backup['eliminados'] . ' eliminados',
            ]]
        );

        return self::SUCCESS;
    }
}
