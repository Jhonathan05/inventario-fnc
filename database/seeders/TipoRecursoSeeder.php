<?php

namespace Database\Seeders;

use App\Models\TipoRecurso;
use Illuminate\Database\Seeder;

class TipoRecursoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Equipo Escritorio',
            'Equipo Portatil',
            'Equipo Todo En Uno',
            'Escaner',
            'Impresora',
            'Microfono',
            'Mezclador',
            'Componente Microfono',
            'Planta',
            'Tv',
            'Tableta',
            'Telefono',
            'Servidor',
            'Switch',
            'Router',
            'Cajón',
            'Plataforma Vb',
            'Vb',
            'Sin Clasificar',
        ];

        foreach ($tipos as $nombre) {
            TipoRecurso::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
