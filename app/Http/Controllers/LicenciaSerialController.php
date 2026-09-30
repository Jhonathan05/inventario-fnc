<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LicenciaSerialController extends Controller
{
    public function store(Request $request, \App\Models\Licencia $licencia)
    {
        $validated = $request->validate([
            'serial' => 'required|string|max:255',
            'estado' => 'required|in:Disponible,Inactivo',
            'observaciones' => 'nullable|string',
        ]);

        $validated['licencia_id'] = $licencia->id;
        $serial = \App\Models\LicenciaSerial::create($validated);

        \App\Models\LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
            'accion' => 'Creación de serial',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => "Serial {$serial->serial} agregado al inventario.",
        ]);

        return back()->with('success', 'Serial agregado correctamente.');
    }

    public function update(Request $request, \App\Models\Licencia $licencia, \App\Models\LicenciaSerial $serial)
    {
        $validated = $request->validate([
            'serial' => 'required|string|max:255',
            'estado' => 'required|in:Disponible,Inactivo',
            'observaciones' => 'nullable|string',
        ]);

        if ($serial->estado == 'Asignado') {
            // Si está asignado, solo puede actualizar observaciones (y tal vez estado Inactivo, pero mejor no tocar serial)
            $serial->update(['observaciones' => $validated['observaciones']]);
        } else {
            $serial->update($validated);
        }

        \App\Models\LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
            'accion' => 'Actualización de serial',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => "Serial {$serial->serial} actualizado.",
        ]);

        return back()->with('success', 'Serial actualizado correctamente.');
    }

    public function destroy(\App\Models\Licencia $licencia, \App\Models\LicenciaSerial $serial)
    {
        if ($serial->estado === 'Asignado' || $serial->estado === 'Reservado') {
            return back()->with('error', 'No se puede eliminar un serial que está asignado o reservado.');
        }

        \App\Models\LicenciaHistorial::create([
            'fecha' => now(),
            'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
            'accion' => 'Eliminación de serial',
            'licencia_nombre' => $licencia->nombre,
            'observacion' => "Serial {$serial->serial} eliminado del inventario.",
        ]);

        $serial->delete();

        return back()->with('success', 'Serial eliminado correctamente.');
    }
}
