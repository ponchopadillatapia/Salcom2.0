<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoEstatusNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $folio,
        public string $estatus,
        public string $nombreCliente,
        public ?string $notas = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido {$this->folio} — {$this->estatus}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pedido-estatus',
        );
    }
}
