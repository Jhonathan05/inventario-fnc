<?php

namespace App\Http\Controllers;

use App\Services\LimpiarInventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Limpieza del inventario de activos (Lote B8) — botón con aceptación
 * condicionada en el módulo Importar:
 *
 *  1. El modal muestra exactamente cuántas filas se van a borrar (en vivo).
 *  2. Exige marcar «comprendo que no se puede deshacer».
 *  3. Exige escribir la palabra LIMPIAR para habilitar el botón.
 *
 * Preparado para reimportar una nueva versión del CMDB desde cero.
 */
class InventarioController extends Controller
{
    public function __construct(private LimpiarInventarioService $limpieza) {}

    public function limpiar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'acepto'        => ['required', 'accepted'],
            'confirmacion'  => ['required', 'string'],
        ], [
            'acepto.accepted'       => 'Debes confirmar que comprendes que la acción no se puede deshacer.',
            'confirmacion.required' => 'Debes escribir la palabra de confirmación.',
        ]);

        if (mb_strtoupper(trim((string) $datos['confirmacion'])) !== 'LIMPIAR') {
            return back()->withErrors(['confirmacion' => 'La palabra de confirmación no coincide: escribe LIMPIAR.']);
        }

        $resultado = $this->limpieza->limpiar();
        $borradas = $resultado['borradas'];
        $total = array_sum($borradas) - $resultado['equipos']; // sin contar equipos dos veces

        return redirect()
            ->route('equipos.importar.form')
            ->with('success', sprintf(
                'Base de datos de inventario limpia: %d activos y %d registros dependientes borrados (movimientos, correcciones, préstamos, tickets…). Listo para importar la nueva versión del CMDB.',
                $resultado['equipos'],
                max(0, $total)
            ));
    }
}
