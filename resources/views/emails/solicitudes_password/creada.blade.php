<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de cambio de contraseña</title>
</head>
<body style="margin:0; padding:0; background:#f7f7f7; font-family:Arial, sans-serif; color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f7f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e5e5;">
                    <tr>
                        <td style="background:#9e052b; color:#fff; padding:16px 24px; font-size:18px; font-weight:bold;">
                            Solicitud de cambio de contraseña
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 12px;">El siguiente usuario ha solicitado el cambio de su contraseña.</p>

                            <p style="margin:0 0 8px;"><strong>Nombre:</strong> {{ $solicitud->usuario?->name ?? 'N/A' }}</p>
                            <p style="margin:0 0 8px;"><strong>Correo:</strong> {{ $solicitud->email }}</p>
                            <p style="margin:0 0 8px;"><strong>Fecha:</strong> {{ optional($solicitud->created_at)->format('d/m/Y') }}</p>
                            <p style="margin:0 0 8px;"><strong>Hora:</strong> {{ optional($solicitud->created_at)->format('H:i:s') }}</p>
                            <p style="margin:0 0 8px;"><strong>Dirección IP:</strong> {{ $solicitud->ip ?? 'N/A' }}</p>

                            <div style="margin-top:20px;">
                                <a href="{{ Route::has('solicitudes-password.index') ? route('solicitudes-password.index') : url('/') }}" style="display:inline-block; background:#9e052b; color:#fff; text-decoration:none; padding:10px 16px; border-radius:6px; font-weight:600;">
                                    Ir al sistema
                                </a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
