<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpinionPositivaAviso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreProveedor,
        public string $estatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Opinión de Cumplimiento SAT — Actualización requerida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.opinion-positiva',
        );
    }
}
