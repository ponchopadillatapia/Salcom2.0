<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudAltaAprobada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreProveedor,
        public string $correo,
        public string $usuario = '',
        public string $urlLogin = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu solicitud de alta fue aprobada — Portal de Proveedores Salcom',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-alta-aprobada',
        );
    }
}
