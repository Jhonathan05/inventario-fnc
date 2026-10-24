<?php

namespace Database\Seeders;

use App\Models\Funcionario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FuncionariosCmdbSeeder — maestro de personas del CMDB.
 *
 * Fuente: hoja "Listado funcionarios" de `docs/Formatos/CMDB USUARIO FINAL.xlsx`
 * (extraída a `database/seeders/data/funcionarios_cmdb.json`: 4.241 únicos;
 * 2 cédulas duplicadas se omiten conservando la primera aparición).
 *
 * Regla del contrato (§9): el MAESTRO manda para los datos de la persona;
 * Microinformatica manda para la custodia de cada activo. La importación de
 * activos sólo ENLAZA por hash de cédula (crea el funcionario si no existe,
 * nunca sobreescribe al maestro).
 *
 * Idempotente: upsert por identificacion_hash; los campos del maestro no se
 * pisan si ya tienen valor (respeta correcciones hechas desde la app).
 */
class FuncionariosCmdbSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $ruta = database_path('seeders/data/funcionarios_cmdb.json');

        if (! is_file($ruta)) {
            $this->command?->warn('Sin database/seeders/data/funcionarios_cmdb.json — se omite el maestro.');

            return;
        }

        $filas = json_decode((string) file_get_contents($ruta), true) ?: [];

        $creados = 0;
        $actualizados = 0;

        foreach ($filas as $fila) {
            $cedula = (string) ($fila['identificacion'] ?? '');
            if ($cedula === '') {
                continue;
            }

            $hash = hash_hmac('sha256', $cedula, config('app.key'));
            $datos = [
                'identificacion'     => $cedula,
                'identificacion_hash' => $hash,
                'nombres'            => $fila['nombres'] ?? null,
                'apellidos'          => $fila['apellidos'] ?? null,
                'cargo'              => $fila['cargo'] ?? null,
                'area'               => $fila['area'] ?? null,
                'departamento'       => $fila['departamento'] ?? null,
                'ciudad'             => $fila['ciudad'] ?? null,
                'empresa_funcionario' => $fila['empresa'] ?? null,
                'div'                => $fila['div'] ?? null,
                'email'              => $fila['email'] ?? null,
                'jefe_cedula'        => $fila['jefe_cedula'] ?? null,
                'jefe_nombre'        => $fila['jefe_nombre'] ?? null,
                'jefe_email'         => $fila['jefe_email'] ?? null,
                'genero'             => $fila['genero'] ?? null,
                'ceco'               => $fila['ceco'] ?? null,
                'fecha_vencimiento'  => $fila['fecha_vencimiento'] ?? null,
                'proceso'            => $fila['proceso'] ?? null,
                'tipo_recurso'       => $fila['tipo_recurso'] ?? null,
                'estado'             => 'Activo',
                'deleted_at'         => null,
            ];

            $existente = Funcionario::withTrashed()->where('identificacion_hash', $hash)->first();

            if ($existente === null) {
                Funcionario::create($datos);
                $creados++;
                continue;
            }

            // Idempotencia suave: sólo rellena campos vacíos del maestro.
            $rellenar = collect($datos)
                ->except(['identificacion', 'identificacion_hash', 'estado', 'deleted_at'])
                ->filter(fn ($valor) => $valor !== null && $valor !== '')
                ->reject(fn ($valor, $campo) => filled($existente->{$campo}))
                ->all();

            if ($rellenar !== []) {
                $existente->fill($rellenar)->save();
                $actualizados++;
            }

            if ($existente->trashed()) {
                $existente->restore();
            }
        }

        $this->command?->info("✔ Maestro de funcionarios: {$creados} creados, {$actualizados} completados (de " . count($filas) . ' del CMDB).');
    }
}
