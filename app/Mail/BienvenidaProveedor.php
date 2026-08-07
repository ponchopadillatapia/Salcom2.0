<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaProveedor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreProveedor,
        public string $correo,
        public string $usuario = '',
        public string $urlVerificacion = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirma tu correo — Portal de Proveedores Salcom',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenida-proveedor',
        );
    }
}
