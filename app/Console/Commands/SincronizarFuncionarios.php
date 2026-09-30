<?php

namespace App\Console\Commands;

use App\Models\Funcionario;
use App\Models\UsuarioAsignado;
use Illuminate\Console\Command;

class SincronizarFuncionarios extends Command
{
    protected $signature   = 'funcionarios:sincronizar';
    protected $description = 'Migra los usuarios asignados existentes a la tabla de funcionarios';

    public function handle(): int
    {
        $usuarios = UsuarioAsignado::all();
        $creados  = 0;
        $omitidos = 0;

        $this->info("Procesando {$usuarios->count()} usuario(s) asignado(s)...");
        $bar = $this->output->createProgressBar($usuarios->count());
        $bar->start();

        foreach ($usuarios as $usuario) {
            $cedula = trim($usuario->cedula ?? '');

            if (empty($cedula)) {
                $omitidos++;
                $bar->advance();
                continue;
            }

            // Separar nombre completo en nombres y apellidos
            $nombreCompleto = trim($usuario->nombre ?? '');
            $partes         = explode(' ', $nombreCompleto, 2);
            $nombres        = $partes[0] ?? $nombreCompleto;
            $apellidos      = $partes[1] ?? null;

            $identificacionNormalizada = \App\Services\Importadores\CMDBMapperService::normalizeIdentifier($cedula, true) ?? '';
            $hash = hash_hmac('sha256', $identificacionNormalizada, config('app.key'));

            Funcionario::withTrashed()->updateOrCreate(
                ['identificacion_hash' => $hash],
                [
                    'identificacion'      => $cedula,
                    'nombres'             => $nombres,
                    'apellidos'           => $apellidos,
                    'cargo'               => $usuario->cargo,
                    'area'                => $usuario->area,
                    'departamento'        => $usuario->departamento,
                    'ciudad'              => $usuario->ciudad,
                    'empresa_funcionario' => $usuario->empresa_funcionario,
                    'tipo_vinculacion'    => $usuario->tipo_vinculacion,
                    'estado'              => 'Activo',
                    'deleted_at'          => null,
                ]
            );

            $creados++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Sincronización completa: {$creados} funcionario(s) creados/actualizados, {$omitidos} omitidos (sin cédula).");

        return Command::SUCCESS;
    }
}
