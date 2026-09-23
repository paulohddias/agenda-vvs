<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enviado ao cliente assim que o agendamento é feito (já nasce confirmado).
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
            subject: 'Agendamento confirmado — Via Vale Sistemas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment',
            with: [
                'appointment' => $this->appointment,
                'title' => 'Agendamento confirmado',
                'badge' => 'Horário confirmado',
                'badgeClass' => '',
                'intro' => 'Seu agendamento para emissão de certificado digital está confirmado. Confira os detalhes abaixo:',
                'closing' => 'Seu horário já está <strong>reservado</strong>. Esperamos por você!',
                'previousStart' => null,
                'cancelled' => false,
            ],
        );
    }
}
