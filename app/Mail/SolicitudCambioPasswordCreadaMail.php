<?php

namespace App\Mail;

use App\Models\SolicitudCambioPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudCambioPasswordCreadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SolicitudCambioPassword $solicitud)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de cambio de contraseña registrada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitudes_password.creada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
