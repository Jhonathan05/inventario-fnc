<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudCambioPassword\FilterSolicitudCambioPasswordRequest;
use App\Http\Requests\SolicitudCambioPassword\RejectSolicitudCambioPasswordRequest;
use App\Http\Requests\SolicitudCambioPassword\StoreSolicitudCambioPasswordRequest;
use App\Http\Requests\SolicitudCambioPassword\UpdateSolicitudCambioPasswordRequest;
use App\Mail\SolicitudCambioPasswordCreadaMail;
use App\Models\AuditLog;
use App\Models\SolicitudCambioPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SolicitudCambioPasswordController extends Controller
{
    public function index(FilterSolicitudCambioPasswordRequest $request): View
    {
        Gate::authorize('viewAny', SolicitudCambioPassword::class);

        $query = SolicitudCambioPassword::query()
            ->with(['usuario', 'administrador'])
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->date('fecha'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar): void {
                $subQuery->where('email', 'like', "%{$buscar}%")
                    ->orWhere('estado', 'like', "%{$buscar}%")
                    ->orWhereHas('usuario', function ($userQuery) use ($buscar): void {
                        $userQuery->where('name', 'like', "%{$buscar}%")
                            ->orWhere('email', 'like', "%{$buscar}%");
                    });
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        return view('solicitudes_password.index', compact('solicitudes'));
    }

    public function storePublic(StoreSolicitudCambioPasswordRequest $request): RedirectResponse
    {
        $usuario = User::query()
            ->where('email', $request->string('email'))
            ->firstOrFail();

        $solicitud = SolicitudCambioPassword::query()->create([
            'user_id' => $usuario->id,
            'email' => $usuario->email,
            'estado' => SolicitudCambioPassword::ESTADO_PENDIENTE,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $usuario->id,
            'user_name' => $usuario->name,
            'action' => 'Solicitud de cambio de contraseña creada',
            'details' => 'Solicitud #' . $solicitud->id . ' creada desde login. IP: ' . ($request->ip() ?? 'N/A'),
        ]);

        $admins = User::role('Administrador')
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        if (! empty($admins)) {
            Mail::to($admins)->send(new SolicitudCambioPasswordCreadaMail($solicitud->load('usuario')));

            AuditLog::create([
                'user_id' => $usuario->id,
                'user_name' => $usuario->name,
                'action' => 'Notificación de solicitud enviada',
                'details' => 'Se notificó por correo a administradores sobre la solicitud #' . $solicitud->id,
            ]);
        } else {
            AuditLog::create([
                'user_id' => $usuario->id,
                'user_name' => $usuario->name,
                'action' => 'Notificación de solicitud no enviada',
                'details' => 'No se encontraron administradores con correo para la solicitud #' . $solicitud->id,
            ]);
        }

        return back()->with('success', 'La solicitud fue enviada correctamente al Administrador del sistema.');
    }

    public function show(Request $request, SolicitudCambioPassword $solicitud): View
    {
        Gate::authorize('view', $solicitud);

        $solicitud->load(['usuario', 'administrador']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'Solicitud de cambio de contraseña consultada',
            'details' => 'El administrador abrió el detalle de la solicitud #' . $solicitud->id . '. IP: ' . ($request->ip() ?? 'N/A') . '. Fecha: ' . now()->format('d/m/Y H:i:s'),
        ]);

        return view('solicitudes_password.show', compact('solicitud'));
    }

    public function editPassword(SolicitudCambioPassword $solicitud): View
    {
        Gate::authorize('update', $solicitud);

        $solicitud->load('usuario');

        return view('solicitudes_password.edit-password', compact('solicitud'));
    }

    public function updatePassword(UpdateSolicitudCambioPasswordRequest $request, SolicitudCambioPassword $solicitud): RedirectResponse
    {
        Gate::authorize('update', $solicitud);

        if ($solicitud->estado !== SolicitudCambioPassword::ESTADO_PENDIENTE) {
            return redirect()
                ->route('solicitudes-password.show', $solicitud)
                ->with('warning', 'Solo se pueden atender solicitudes en estado Pendiente.');
        }

        DB::transaction(function () use ($request, $solicitud): void {
            $usuario = $solicitud->usuario()->firstOrFail();

            $usuario->update([
                'password' => Hash::make((string) $request->input('password')),
            ]);

            $solicitud->update([
                'estado' => SolicitudCambioPassword::ESTADO_ATENDIDA,
                'observacion' => $request->filled('observacion') ? (string) $request->input('observacion') : null,
                'administrador_id' => Auth::id(),
                'fecha_atencion' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
                'action' => 'Contraseña actualizada por administrador',
                'details' => 'Solicitud #' . $solicitud->id . ' atendida. Usuario afectado: ' . $usuario->email . '. IP: ' . ($request->ip() ?? 'N/A') . '. Fecha: ' . now()->format('d/m/Y H:i:s'),
            ]);
        });

        return redirect()
            ->route('solicitudes-password.show', $solicitud)
            ->with('success', 'La contraseña fue actualizada y la solicitud quedó como Atendida.');
    }

    public function reject(RejectSolicitudCambioPasswordRequest $request, SolicitudCambioPassword $solicitud): RedirectResponse
    {
        Gate::authorize('reject', $solicitud);

        if ($solicitud->estado !== SolicitudCambioPassword::ESTADO_PENDIENTE) {
            return redirect()
                ->route('solicitudes-password.show', $solicitud)
                ->with('warning', 'Solo se pueden rechazar solicitudes en estado Pendiente.');
        }

        $solicitud->update([
            'estado' => SolicitudCambioPassword::ESTADO_RECHAZADA,
            'observacion' => (string) $request->input('observacion'),
            'administrador_id' => Auth::id(),
            'fecha_atencion' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'Solicitud de cambio de contraseña rechazada',
            'details' => 'Solicitud #' . $solicitud->id . ' rechazada. Motivo: ' . (string) $request->input('observacion') . '. IP: ' . ($request->ip() ?? 'N/A') . '. Fecha: ' . now()->format('d/m/Y H:i:s'),
        ]);

        return redirect()
            ->route('solicitudes-password.show', $solicitud)
            ->with('success', 'La solicitud fue rechazada correctamente.');
    }
}
