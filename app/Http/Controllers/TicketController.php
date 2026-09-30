<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['funcionario', 'responsable']);

        if ($request->filled('buscar')) {
            $buscar = strtolower($request->buscar);
            $query->where(function($q) use ($buscar) {
                $q->where('titulo', 'like', "%{$buscar}%")
                  ->orWhere('id', $buscar);
            });
            // La búsqueda por funcionario cifrado se hará después en PHP
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Si estamos buscando, necesitamos filtrar en memoria para los campos cifrados del Funcionario
        if ($request->filled('buscar')) {
            $buscar = strtolower($request->buscar);
            // Quitamos la paginación a nivel de base de datos momentáneamente para filtrar TODO
            $todos = $query->orderByDesc('created_at')->get();
            $filtrados = $todos->filter(function($t) use ($buscar) {
                // Ya la BD filtró por titulo o id, pero como era OR, revisamos si coincide aquí también
                // o si coincide en el funcionario cifrado
                if (str_contains(strtolower($t->titulo), $buscar) || (string)$t->id === $buscar) return true;
                
                if ($t->funcionario) {
                    if (str_contains(strtolower($t->funcionario->nombres ?? ''), $buscar)) return true;
                    if (str_contains(strtolower($t->funcionario->identificacion ?? ''), $buscar)) return true;
                }
                return false;
            });
            
            $page = request()->get('page', 1);
            $perPage = 15;
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
                $filtrados->slice(($page - 1) * $perPage, $perPage)->values(),
                $filtrados->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $tickets = $query->orderByDesc('created_at')->paginate(15);
        }
        
        // Métricas para el Dashboard
        $totalTickets = Ticket::count();
        $ticketsAbiertos = Ticket::whereIn('estado', ['Abierto', 'En Proceso'])->count();
        $ticketsPendientes = Ticket::where('estado', 'Pendiente')->count();
        $ticketsCriticos = Ticket::where('prioridad', 'Crítica')->whereNotIn('estado', ['Resuelto', 'Cerrado'])->count();
        $ticketsResueltos = Ticket::whereIn('estado', ['Resuelto', 'Cerrado'])->count();
        
        return view('tickets.index', compact(
            'tickets', 'totalTickets', 'ticketsAbiertos', 'ticketsPendientes', 'ticketsCriticos', 'ticketsResueltos'
        ));
    }

    public function create()
    {
        $funcionarios = Funcionario::select('id', 'nombres', 'apellidos', 'identificacion')
            ->where('estado', 'activo')
            ->get()
            ->sortBy('nombres')
            ->take(500);
        return view('tickets.create', compact('funcionarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|string',
            'prioridad' => 'required|string',
            'descripcion' => 'required|string',
            'funcionario_id' => 'required|exists:funcionarios,id',
            'equipo_id' => 'nullable|exists:equipos,id',
            'fecha_solicitud' => 'nullable|date',
        ]);

        $ticket = Ticket::create([
            ...$validated,
            'estado' => 'Abierto',
            'fecha_solicitud' => $request->fecha_solicitud ?? now()
        ]);

        if ($request->hasFile('archivos')) {
            $archivos = [];
            foreach ($request->file('archivos') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $archivos[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }
            $ticket->archivos = $archivos;
            $ticket->save();
        }
        
        $ticket->seguimientos()->create([
            'user_id' => Auth::id(),
            'tipo_avance' => 'Creación de Ticket',
            'comentario' => "El ticket fue creado exitosamente en estado Abierto.",
            'is_system' => true,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket creado correctamente');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'funcionario',
            'responsable',
            'diagnosticoPor',
            'equipo.tipoRecurso',
            'equipo.usuarioAsignado',
            'equipo.licenciaAsignaciones.licencia',
            'equipo.historialTecnico' => fn($q) => $q->latest('fecha_evento')->limit(5),
            'equipo.asignaciones' => fn($q) => $q->latest('fecha_accion')->limit(5),
            'seguimientos.user'
        ]);

        try {
            $tecnicos = User::role('Analista Tic')->select('id', 'name')->get();
            if ($tecnicos->isEmpty()) throw new \Exception();
        } catch (\Exception $e) {
            $tecnicos = User::select('id', 'name')->get();
        }

        return view('tickets.show', compact('ticket', 'tecnicos'));
    }

    public function cambiarEstado(Request $request, Ticket $ticket)
    {
        $request->validate([
            'estado' => 'required|string',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $estadoAnterior = $ticket->estado;
        $responsableAnterior = $ticket->user_id;
        
        $ticket->estado = $request->estado;
        if ($request->has('user_id')) {
            $ticket->user_id = $request->user_id;
        }
        $ticket->save();

        if ($estadoAnterior !== $ticket->estado) {
            $ticket->seguimientos()->create([
                'user_id' => Auth::id(),
                'tipo_avance' => 'Cambio de Estado',
                'comentario' => "El ticket pasó de '{$estadoAnterior}' a '{$ticket->estado}'.",
                'is_system' => true,
            ]);
        }

        if ($request->has('user_id') && $responsableAnterior != $request->user_id) {
            $nuevoResponsable = User::find($request->user_id)->name ?? 'N/A';
            $ticket->seguimientos()->create([
                'user_id' => Auth::id(),
                'tipo_avance' => 'Reasignación',
                'comentario' => "El ticket fue asignado a {$nuevoResponsable}.",
                'is_system' => true,
            ]);
        }

        return back()->with('success', 'Información general actualizada correctamente.');
    }

    public function updateDiagnostico(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'diagnostico_inicial' => 'required|string',
            'causa_probable' => 'nullable|string',
            'observaciones_tecnicas' => 'nullable|string',
        ]);

        $ticket->update([
            'diagnostico_inicial' => $validated['diagnostico_inicial'],
            'causa_probable' => $validated['causa_probable'],
            'observaciones_tecnicas' => $validated['observaciones_tecnicas'],
            'fecha_diagnostico' => $ticket->fecha_diagnostico ?? now(),
            'diagnostico_user_id' => $ticket->diagnostico_user_id ?? Auth::id(),
        ]);

        $ticket->seguimientos()->create([
            'user_id' => Auth::id(),
            'tipo_avance' => 'Diagnóstico Registrado',
            'comentario' => "Se ha registrado o actualizado el diagnóstico inicial del caso.",
            'is_system' => true,
        ]);

        return back()->with('success', 'Diagnóstico registrado correctamente.');
    }

    public function storeSeguimiento(Request $request, Ticket $ticket)
    {
        $request->validate([
            'comentario' => 'required|string',
            'tipo_avance' => 'required|string',
        ]);

        $ticket->seguimientos()->create([
            'user_id' => Auth::id(),
            'tipo_avance' => $request->tipo_avance,
            'comentario' => $request->comentario,
            'is_system' => false,
        ]);

        return back()->with('success', 'Avance registrado correctamente.');
    }

    public function updateSolucion(Request $request, Ticket $ticket)
    {
        if ($request->boolean('cerrar_ticket')) {
            $request->validate([
                'solucion_aplicada' => 'required|string',
                'tiempo_invertido' => 'required|string',
                'observaciones_finales' => 'required|string',
            ], [
                'solucion_aplicada.required' => 'La solución aplicada es obligatoria para cerrar el ticket.',
                'tiempo_invertido.required' => 'El tiempo invertido es obligatorio para cerrar el ticket.',
                'observaciones_finales.required' => 'Las observaciones finales son obligatorias para cerrar el ticket.',
            ]);
        } else {
            $request->validate([
                'solucion_aplicada' => 'required|string',
                'tiempo_invertido' => 'nullable|string',
                'observaciones_finales' => 'nullable|string',
            ]);
        }

        $ticket->solucion_aplicada = $request->solucion_aplicada;
        $ticket->tiempo_invertido = $request->tiempo_invertido;
        $ticket->observaciones_finales = $request->observaciones_finales;
        $ticket->fecha_solucion = $ticket->fecha_solucion ?? now();
        
        if ($request->boolean('cerrar_ticket')) {
            $ticket->estado = 'Cerrado';
            $ticket->fecha_cierre = now();
        } else {
            if (!in_array($ticket->estado, ['Resuelto', 'Cerrado'])) {
                $ticket->estado = 'Resuelto';
            }
        }
        
        $ticket->save();

        $ticket->seguimientos()->create([
            'user_id' => Auth::id(),
            'tipo_avance' => 'Solución Registrada',
            'comentario' => "Se ha registrado la solución del caso. Estado actual: {$ticket->estado}.",
            'is_system' => true,
        ]);

        return back()->with('success', 'Solución registrada correctamente.');
    }

    public function uploadEvidencia(Request $request, Ticket $ticket)
    {
        $request->validate([
            'archivos' => 'required|array',
            'archivos.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip|max:5120' // Max 5MB per file
        ]);

        $archivos = $ticket->archivos ?? [];

        foreach ($request->file('archivos') as $file) {
            $path = $file->store('tickets/' . $ticket->id, 'public');
            $archivos[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toDateTimeString()
            ];
        }

        $ticket->archivos = $archivos;
        $ticket->save();

        $ticket->seguimientos()->create([
            'user_id' => Auth::id(),
            'tipo_avance' => 'Evidencia Anexada',
            'comentario' => "El usuario ha anexado nuevos archivos de evidencia al ticket.",
            'is_system' => true,
        ]);

        return back()->with('success', 'Evidencias anexadas correctamente.');
    }

    public function descargarEvidencia(Ticket $ticket, $index)
    {
        $archivos = $ticket->archivos ?? [];
        if (!isset($archivos[$index])) {
            abort(404, 'Archivo no encontrado');
        }

        $file = $archivos[$index];
        if (!Storage::disk('public')->exists($file['path'])) {
            abort(404, 'El archivo no existe físicamente en el servidor');
        }

        return Storage::disk('public')->download($file['path'], $file['name']);
    }
}
