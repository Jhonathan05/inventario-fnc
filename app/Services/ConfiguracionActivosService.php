<?php

namespace App\Services;

class ConfiguracionActivosService
{
    /**
     * Define y retorna los campos que deben estar visibles y activos
     * para un prefijo de Tipo de Recurso específico.
     * 
     * Esta es la ÚNICA matriz de verdad para el Frontend, Backend e Importaciones.
     * 
     * @param string $prefijo
     * @return array
     */
    public static function getCamposVisibles(string $prefijo): array
    {
        // Campos Mínimos (Identificación básica que aplica a todo)
        $base = [
            'marca', 
            'modelo', 
            'serial', 
            'activo_fijo', 
            'placa'
        ];

        // Campo lógico (obligatorio en DB pero no siempre tiene sentido mostrarlo, ej. Cajón o Guaya)
        $logicos = ['nombre_equipo'];

        // Campos Técnicos Computacionales
        $hardware = [
            'procesador', 
            'ram', 
            'disco', 
            'sistema_operativo'
        ];

        // Campos de Vida Útil / Garantía
        $garantia = [
            'fecha_compra', 
            'fin_garantia', 
            'tiempo_uso'
        ];

        // Periféricos agrupados
        $perifericos = [
            'periferico_telefono', 
            'periferico_teclado', 
            'periferico_mouse', 
            'periferico_camara'
        ];

        // Configuración específica por Prefijo (3 letras)
        $config = [
            // Computadores y Servidores: Todo visible
            'POR' => array_merge($base, $logicos, $hardware, $garantia, $perifericos), // Portátil
            'ESC' => array_merge($base, $logicos, $hardware, $garantia, $perifericos), // Escritorio
            'TEU' => array_merge($base, $logicos, $hardware, $garantia, $perifericos), // Todo En Uno
            'MIC' => array_merge($base, $logicos, $hardware, $garantia, $perifericos), // Micro
            'SER' => array_merge($base, $logicos, $hardware, $garantia), // Servidor
            'TAB' => array_merge($base, $logicos, $hardware, $garantia), // Tablet

            // Impresoras / Escáneres / Teléfonos: Sin Hardware interno pero con lógica de red y garantía
            'IMP' => array_merge($base, $logicos, $garantia),
            'ESN' => array_merge($base, $logicos, $garantia),
            'TEL' => array_merge($base, $logicos, $garantia),

            // Elementos de Red: Sin hardware complejo, pero con lógica de red
            'ROU' => array_merge($base, $logicos), // Router
            'SWI' => array_merge($base, $logicos), // Switch

            // Elementos Simples (Cajón, Guaya, Componentes como Disco Sólido, Monitores)
            // No requieren hostname (nombre_equipo) lógicamente
            'CAJ' => $base, // Cajón
            'GUA' => $base, // Guaya
            'MON' => $base, // Monitor
            'SSD' => $base, // Disco Sólido
            'TEC' => $base, // Teclado
            'MOU' => $base, // Mouse
            'CAM' => $base, // Cámara
            'TLV' => array_merge($base, $logicos, $garantia), // Televisor / Smart TV
        ];

        // Retornar la configuración específica, o el fallback completo si es un tipo nuevo o desconocido
        return $config[$prefijo] ?? array_merge($base, $logicos, $hardware, $garantia, $perifericos);
    }
}
