<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo genérico del FLUJO DE PAGO (petición de Alexa).
 *
 * POR QUÉ una sola clase para los 4 avisos (generado/autorizado/rechazado/pagado): todos son el
 * mismo tipo de correo (un título, unas líneas de detalle y un botón opcional). En vez de 4 clases
 * casi idénticas, una sola parametrizable. El contenido concreto se arma en quien la dispara.
 *
 * @param  array<int, string>  $lineas  líneas de cuerpo (cada una un párrafo)
 */
class NotificacionFlujoPago extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $asunto,
        public string $titulo,
        public array $lineas = [],
        public ?string $urlBoton = null,
        public ?string $textoBoton = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->asunto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.flujo-pago',
        );
    }
}
