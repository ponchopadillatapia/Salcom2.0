<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PagoConfirmadoProveedor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreProveedor,
        public string $estatusFactura,
        public int $numFacturas,
        public float $montoTotal,
        public ?string $fechaPago,
        public string $urlPagos = '',
    ) {}

    public function envelope(): Envelope
    {
        $asunto = $this->estatusFactura === 'pagada'
            ? 'Tu pago fue confirmado — Portal de Proveedores Salcom'
            : 'Tu pago fue programado — Portal de Proveedores Salcom';

        return new Envelope(subject: $asunto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pago-confirmado-proveedor',
        );
    }
}
