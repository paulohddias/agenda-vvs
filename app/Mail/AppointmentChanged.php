<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Avisa o cliente quando a equipe mexe no agendamento pelo painel: confirmou,
 * mudou o horário ou cancelou. Mesmo visual do e-mail de agendamento feito.
 * Enviado na hora, sem fila, pelo mesmo motivo do AppointmentRequested.
 */
class AppointmentChanged extends Mailable
{
    use Queueable, SerializesModels;

    public const CONFIRMED = 'confirmed';
    public const RESCHEDULED = 'rescheduled';
    public const CANCELLED = 'cancelled';

    public function __construct(
        public Appointment $appointment,
        public string $change,
        public ?Carbon $previousStart = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->texts()['title'].' — Via Vale Sistemas');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment',
            with: $this->texts() + [
                'appointment' => $this->appointment,
                'previousStart' => $this->change === self::RESCHEDULED ? $this->previousStart : null,
                'cancelled' => $this->change === self::CANCELLED,
                'rescheduleUrl' => $this->change === self::CANCELLED ? null : $this->appointment->customerRescheduleUrl(),
            ],
        );
    }

    /** @return array{title: string, badge: string, badgeClass: string, intro: string, closing: string} */
    private function texts(): array
    {
        return match ($this->change) {
            self::RESCHEDULED => [
                'title' => 'Horário alterado',
                'badge' => 'Horário alterado',
                'badgeClass' => 'badge-blue',
                'intro' => 'O horário do seu agendamento para emissão de certificado digital foi alterado. Confira o novo horário abaixo:',
                'closing' => 'Seu novo horário já está <strong>reservado</strong>. Esperamos por você!',
            ],
            self::CANCELLED => [
                'title' => 'Agendamento cancelado',
                'badge' => 'Cancelado',
                'badgeClass' => 'badge-red',
                'intro' => 'O agendamento abaixo foi cancelado:',
                'closing' => 'Se quiser marcar um novo horário, é só acessar <a href="'.e(url('/')).'">'.e(url('/')).'</a>.',
            ],
            default => [
                'title' => 'Agendamento confirmado',
                'badge' => 'Horário confirmado',
                'badgeClass' => '',
                'intro' => 'Seu agendamento para emissão de certificado digital foi confirmado pela nossa equipe. Confira os detalhes abaixo:',
                'closing' => 'Seu horário está <strong>reservado</strong>. Esperamos por você!',
            ],
        };
    }
}
