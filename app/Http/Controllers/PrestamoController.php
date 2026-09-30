<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Equipo;
use App\Models\HistorialAdministrativo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $query = Prestamo::with(['equipo', 'registradoPor'])->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('buscar')) {
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                $q->where('persona_nombre', 'like', "%{$busqueda}%")
                  ->orWhere('persona_documento', 'like', "%{$busqueda}%")
                  ->orWhereHas('equipo', function($q2) use ($busqueda) {
                      $q2->where('placa', 'like', "%{$busqueda}%")
                         ->orWhere('serial', 'like', "%{$busqueda}%")
                         ->orWhere('nombre_equipo', 'like', "%{$busqueda}%");
                  });
            });
        }

        $prestamos = $query->paginate(15)->withQueryString();

        return view('prestamos.index', compact('prestamos'));
    }

    public function create(Request $request)
    {
        // Si viene un equipo preseleccionado
        $equipoPreseleccionado = null;
        if ($request->filled('equipo_id')) {
            $equipoPreseleccionado = Equipo::find($request->equipo_id);
            if ($equipoPreseleccionado && !$equipoPreseleccionado->estaDisponibleParaPrestamo()) {
                return back()->with('error', 'El equipo seleccionado no está disponible para préstamo.');
            }
        }

        $equiposDisponibles = Equipo::with('tipoRecurso')->where('estado_operativo', 'disponible')->get()->filter(function ($eq) {
            return $eq->estaDisponibleParaPrestamo();
        });

        return view('prestamos.create', compact('equiposDisponibles', 'equipoPreseleccionado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'equipo_id' => 'required|exists:equipos,id',
            'persona_nombre' => 'required|string|max:255',
            'persona_documento' => 'nullable|string|max:255',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_devolucion_prevista' => 'required|date|after:fecha_inicio',
            'duracion' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ], [
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior al día de hoy.',
            'fecha_devolucion_prevista.after' => 'La fecha de devolución debe ser posterior a la fecha de inicio.'
        ]);

        try {
            DB::beginTransaction();

            $equipo = Equipo::findOrFail($request->equipo_id);

            // Regla crítica: Disponibilidad (Backend)
            if (!$equipo->estaDisponibleParaPrestamo()) {
                throw new \Exception('El equipo ya no se encuentra disponible para préstamo (puede haber sido asignado o prestado recientemente).');
            }

            $prestamo = Prestamo::create([
                'equipo_id' => $equipo->id,
                'persona_nombre' => $request->persona_nombre,
                'persona_documento' => $request->persona_documento,
                'user_id' => auth()->id(),
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_devolucion_prevista' => $request->fecha_devolucion_prevista,
                'duracion' => $request->duracion,
                'estado' => 'Activo',
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
            ]);

            HistorialAdministrativo::create([
                'equipo_id' => $equipo->id,
                'user_id' => auth()->id(),
                'tipo_cambio' => 'prestamo_iniciado',
                'campo_modificado' => 'prestamo',
                'valor_anterior' => null,
                'valor_nuevo' => "Prestado a: {$prestamo->persona_nombre}",
                'descripcion' => "Préstamo iniciado. Devolución prevista: {$prestamo->fecha_devolucion_prevista->format('Y-m-d H:i')}. Motivo: {$prestamo->motivo}",
            ]);

            DB::commit();
            return redirect()->route('prestamos.show', $prestamo)->with('success', 'Préstamo registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Prestamo $prestamo)
    {
        $prestamo->load(['equipo', 'registradoPor', 'devueltoPor']);
        return view('prestamos.show', compact('prestamo'));
    }

    public function edit(Prestamo $prestamo)
    {
        return view('prestamos.edit', compact('prestamo')); // Usado para ampliación
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        // Ampliación del préstamo
        $request->validate([
            'nueva_fecha_devolucion' => 'required|date|after:now',
            'motivo_ampliacion' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $fechaAnterior = $prestamo->fecha_devolucion_prevista;
            
            $prestamo->fecha_devolucion_prevista = $request->nueva_fecha_devolucion;
            if ($prestamo->estado === 'Vencido' && $prestamo->fecha_devolucion_prevista > now()) {
                $prestamo->estado = 'Activo';
            }
            $prestamo->save();

            HistorialAdministrativo::create([
                'equipo_id' => $prestamo->equipo_id,
                'user_id' => auth()->id(),
                'tipo_cambio' => 'prestamo_ampliado',
                'campo_modificado' => 'fecha_devolucion_prevista',
                'valor_anterior' => $fechaAnterior->format('Y-m-d H:i'),
                'valor_nuevo' => $prestamo->fecha_devolucion_prevista->format('Y-m-d H:i'),
                'descripcion' => "Préstamo ampliado. Motivo: {$request->motivo_ampliacion}",
            ]);

            DB::commit();
            return redirect()->route('prestamos.show', $prestamo)->with('success', 'Préstamo ampliado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al ampliar el préstamo: ' . $e->getMessage());
        }
    }

    public function registrarDevolucion(Request $request, Prestamo $prestamo)
    {
        $request->validate([
            'estado_fisico_devolucion' => 'required|string',
            'observaciones_devolucion' => 'nullable|string',
        ]);

        if (in_array($prestamo->estado, ['Devuelto', 'Cancelado'])) {
            return back()->with('error', 'Este préstamo ya no está activo.');
        }

        try {
            DB::beginTransaction();

            $prestamo->estado = 'Devuelto';
            $prestamo->fecha_devolucion_real = now();
            $prestamo->usuario_devolucion_id = auth()->id();
            $prestamo->estado_fisico_devolucion = $request->estado_fisico_devolucion;
            $prestamo->observaciones_devolucion = $request->observaciones_devolucion;
            $prestamo->save();

            HistorialAdministrativo::create([
                'equipo_id' => $prestamo->equipo_id,
                'user_id' => auth()->id(),
                'tipo_cambio' => 'prestamo_devuelto',
                'campo_modificado' => 'estado_prestamo',
                'valor_anterior' => 'Activo/Vencido',
                'valor_nuevo' => 'Devuelto',
                'descripcion' => "Préstamo devuelto por {$prestamo->persona_nombre}. Estado físico reportado: {$prestamo->estado_fisico_devolucion}. Observaciones: {$prestamo->observaciones_devolucion}",
            ]);

            // Si el usuario reporta que el estado físico es "Malo" o "Dañado", la lógica existente del inventario
            // podría utilizarse para actualizar el estado del equipo si fuera necesario, pero la regla absoluta
            // dice "Utilizar EXCLUSIVAMENTE la lógica existente del inventario... NO duplicar la lógica".
            // Para mantener la simplicidad y seguridad, solo registramos el estado físico en el préstamo y en el historial.

            DB::commit();
            return redirect()->route('prestamos.show', $prestamo)->with('success', 'Devolución registrada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar la devolución: ' . $e->getMessage());
        }
    }

    public function cancelar(Request $request, Prestamo $prestamo)
    {
        $request->validate([
            'motivo_cancelacion' => 'required|string'
        ]);

        if (in_array($prestamo->estado, ['Devuelto', 'Cancelado'])) {
            return back()->with('error', 'No se puede cancelar un préstamo en este estado.');
        }

        try {
            DB::beginTransaction();
            $estadoAnterior = $prestamo->estado;
            $prestamo->estado = 'Cancelado';
            $prestamo->save();

            HistorialAdministrativo::create([
                'equipo_id' => $prestamo->equipo_id,
                'user_id' => auth()->id(),
                'tipo_cambio' => 'prestamo_cancelado',
                'campo_modificado' => 'estado_prestamo',
                'valor_anterior' => $estadoAnterior,
                'valor_nuevo' => 'Cancelado',
                'descripcion' => "Préstamo cancelado. Motivo: {$request->motivo_cancelacion}",
            ]);

            DB::commit();
            return redirect()->route('prestamos.show', $prestamo)->with('success', 'Préstamo cancelado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cancelar el préstamo: ' . $e->getMessage());
        }
    }
}
