<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\PlantillaPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PdfService
{
    /**
     * Obtiene la plantilla activa para un tipo dado.
     * Si no existe plantilla activa, retorna null (se usará la vista por defecto).
     */
    public function obtenerPlantillaActiva(string $tipo = 'acta_entrega'): ?PlantillaPdf
    {
        return PlantillaPdf::where('tipo', $tipo)
            ->where('activa', true)
            ->latest()
            ->first();
    }

    /**
     * Genera el PDF del acta de entrega para una asignación.
     * Descarga directamente como respuesta HTTP.
     */
    public function generarActaEntrega(Asignacion $asignacion): Response
    {
        $asignacion->load(['equipo.tipoRecurso', 'registradoPor']);
        $equipo = $asignacion->equipo;
        $usuarioSistema = Auth::user()?->name ?? '—';

        $datos = $this->prepararDatos($asignacion, $equipo);

        // Intentar usar plantilla personalizada
        // Intentar usar plantilla personalizada
        $plantilla = $this->obtenerPlantillaActiva('acta_entrega');

        // FORCE use of Blade view to ensure layout changes are applied
        $html = view('pdf.acta_entrega', compact('asignacion', 'equipo', 'datos'))->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        $nombreArchivo = sprintf(
            'acta_entrega_%s_%s.pdf',
            str_replace(' ', '_', $equipo->nombre_equipo ?? $equipo->serial),
            now()->format('Ymd_His')
        );

        return $pdf->stream($nombreArchivo);
    }

    /**
     * Genera el PDF del acta de entrega usando los datos actuales del equipo
     * en lugar de un snapshot de asignación.
     */
    public function generarActaDesdeEquipo(\App\Models\Equipo $equipo): Response
    {
        $equipo->load(['tipoRecurso', 'usuarioAsignado']);
        $usuarioSistema = Auth::user()?->name ?? '—';
        
        $datos = $this->prepararDatosDesdeEquipo($equipo);

        // Intentar usar plantilla personalizada
        // Intentar usar plantilla personalizada
        $plantilla = $this->obtenerPlantillaActiva('acta_entrega');

        // FORCE use of Blade view to ensure layout changes are applied
        $html = view('pdf.acta_entrega', ['equipo' => $equipo, 'asignacion' => null, 'datos' => $datos])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        $nombreArchivo = sprintf(
            'acta_entrega_%s_%s.pdf',
            str_replace(' ', '_', $equipo->nombre_equipo ?? $equipo->serial),
            now()->format('Ymd_His')
        );

        return $pdf->stream($nombreArchivo);
    }

    /**
     * Prepara el array de datos para reemplazo de variables en la plantilla.
     */
    private function prepararDatos(Asignacion $asignacion, \App\Models\Equipo $equipo): array
    {
        return [
            // Equipo
            'nombre_equipo'    => $equipo->nombre_equipo,
            'serial'           => $equipo->serial,
            'activo_fijo'      => $equipo->activo_fijo ?? '—',
            'placa'            => $equipo->placa ?? '—',
            'marca'            => $equipo->marca,
            'modelo'           => $equipo->modelo,
            'tipo_recurso'     => $equipo->tipoRecurso?->nombre ?? '—',
            'procesador'       => $equipo->procesador ?? '—',
            'ram'              => $equipo->ram ?? '—',
            'disco'            => $equipo->disco ?? '—',
            'sistema_operativo'=> $equipo->sistema_operativo ?? '—',
            'estado_operativo' => $equipo->estado_label,
            'fecha_compra'     => $equipo->fecha_compra?->format('d/m/Y') ?? '—',
            'fin_garantia'     => $equipo->fin_garantia?->format('d/m/Y') ?? '—',
            // Usuario asignado (snapshot)
            'nombre_usuario'      => $asignacion->usuario_nombre ?? '—',
            'cedula'              => $asignacion->usuario_cedula ?? '—',
            'cargo'               => $asignacion->usuario_cargo ?? '—',
            'area'                => $asignacion->usuario_area ?? '—',
            'dependencia'         => $asignacion->usuario_dependencia ?? '—',
            'empresa_propietaria' => $asignacion->usuario_empresa_propietaria ?? '—',
            'empresa_funcionario' => $asignacion->usuario_empresa_funcionario ?? '—',
            'departamento'        => $asignacion->usuario_departamento ?? '—',
            'ciudad'              => $asignacion->usuario_ciudad ?? '—',
            'piso'                => $asignacion->usuario_piso ?? '—',
            'distrito'            => $asignacion->usuario_distrito ?? '—',
            'seccional'           => $asignacion->usuario_seccional ?? '—',
            'shortname'           => $asignacion->usuario_shortname ?? '—',
            // Asignación
            'fecha_asignacion' => $asignacion->fecha_accion?->format('d/m/Y H:i') ?? '—',
            'entregado_por'    => $asignacion->entregado_por ?? '—',
            'tipo_accion'      => $asignacion->tipo_accion_label,
            'motivo'           => $asignacion->motivo ?? '—',
            // Sistema
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'usuario_sistema'  => Auth::user()?->name ?? '—',
            // Logos (para plantillas limpias)
            'logo_fnc'         => $this->obtenerLogoPath(),
        ];
    }

    private function obtenerLogoPath(): string
    {
        return 'data:image/jpeg;base64,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';
    }

    /**
     * Prepara el array de datos para reemplazo de variables usando el modelo actual del equipo.
     */
    private function prepararDatosDesdeEquipo(\App\Models\Equipo $equipo): array
    {
        $usuario = $equipo->usuarioAsignado;
        return [
            // Equipo
            'nombre_equipo'    => $equipo->nombre_equipo,
            'serial'           => $equipo->serial,
            'activo_fijo'      => $equipo->activo_fijo ?? '—',
            'placa'            => $equipo->placa ?? '—',
            'marca'            => $equipo->marca,
            'modelo'           => $equipo->modelo,
            'tipo_recurso'     => $equipo->tipoRecurso?->nombre ?? '—',
            'procesador'       => $equipo->procesador ?? '—',
            'ram'              => $equipo->ram ?? '—',
            'disco'            => $equipo->disco ?? '—',
            'sistema_operativo'=> $equipo->sistema_operativo ?? '—',
            'estado_operativo' => ucfirst($equipo->estado_operativo),
            'fecha_compra'     => $equipo->fecha_compra?->format('d/m/Y') ?? '—',
            'fin_garantia'     => $equipo->fin_garantia?->format('d/m/Y') ?? '—',
            // Usuario asignado
            'nombre_usuario'      => $usuario->nombre ?? '—',
            'cedula'              => $usuario->cedula ?? '—',
            'cargo'               => $usuario->cargo ?? '—',
            'area'                => $usuario->area ?? '—',
            'dependencia'         => $usuario->dependencia ?? '—',
            'empresa_propietaria' => $usuario->empresa_propietaria ?? '—',
            'empresa_funcionario' => $usuario->empresa_funcionario ?? '—',
            'departamento'        => $usuario->departamento ?? '—',
            'ciudad'              => $usuario->ciudad ?? '—',
            'piso'                => $usuario->piso ?? '—',
            'distrito'            => $usuario->distrito ?? '—',
            'seccional'           => $usuario->seccional ?? '—',
            'shortname'           => $usuario->shortname ?? '—',
            // Asignación (simulada)
            'fecha_asignacion' => $usuario->created_at?->format('d/m/Y H:i') ?? '—',
            'entregado_por'    => '—', // no se guarda explícitamente en usuarioAsignado
            'tipo_accion'      => 'Asignación Actual',
            'motivo'           => '—',
            // Sistema
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'usuario_sistema'  => Auth::user()?->name ?? '—',
            // Logos
            'logo_fnc'         => $this->obtenerLogoPath(),
        ];
    }

    /**
     * Fuerza que la firma del sistema use el usuario autenticado actual,
     * incluso si la plantilla personalizada tiene texto fijo o placeholder sin reemplazar.
     */
    private function forzarNombreUsuarioSistema(string $contenidoHtml, string $usuarioSistema): string
    {
        $nombre = trim($usuarioSistema) !== '' ? $usuarioSistema : '—';

        // Reemplaza placeholder si quedó sin procesar por variaciones de formato.
        $contenidoHtml = str_replace(['{{usuario_sistema}}', '{{ usuario_sistema }}'], $nombre, $contenidoHtml);

        // Reemplaza texto fijo heredado en plantillas antiguas.
        $contenidoHtml = preg_replace('/>(\s*)administrador(\s*)</i', '>' . $nombre . '<', $contenidoHtml) ?? $contenidoHtml;

        return $contenidoHtml;
    }
}
