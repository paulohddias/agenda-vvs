<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enviado ao cliente assim que o agendamento é solicitado (status "pendente").
 * Não é fila (ShouldQueue): a hospedagem compartilhada não garante um worker de fila
 * rodando o tempo todo, então enviamos na hora mesmo, direto do request.
 */
class AppointmentRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Agendamento solicitado — Via Vale Sistemas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-requested',
            with: ['appointment' => $this->appointment],
        );
    }
}
