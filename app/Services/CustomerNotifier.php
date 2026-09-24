<?php

namespace App\Services;

use App\Mail\AppointmentChanged;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisa o cliente por e-mail que o agendamento mudou (confirmado, reagendado, cancelado),
 * seja pela equipe no painel ou pelo próprio cliente no link de reagendamento.
 */
class CustomerNotifier
{
    /**
     * Falha no envio (SMTP fora do ar, por exemplo) não desfaz a alteração, que já foi salva —
     * só fica no log para dar pra investigar depois.
     */
    public function changed(Appointment $appointment, string $change, ?Carbon $previousStart = null): void
    {
        if (! $appointment->holder_email) {
            return;
        }

        try {
            $appointment->loadMissing('product');
            Mail::to($appointment->holder_email)->send(new AppointmentChanged($appointment, $change, $previousStart));
        } catch (\Throwable $e) {
            Log::error("Falha ao enviar e-mail ({$change}) do agendamento #{$appointment->id}: ".$e->getMessage());
        }
    }
}
