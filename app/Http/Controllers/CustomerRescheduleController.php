<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentChanged;
use App\Models\Appointment;
use App\Models\Staff;
use App\Services\AvailabilityService;
use App\Services\BookingCalendar;
use App\Services\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * O cliente troca o horário sozinho, pelo link assinado que recebe no e-mail/WhatsApp.
 * Mesma tela de calendário do agendamento, sem login e sem precisar preencher nada de novo.
 */
class CustomerRescheduleController extends Controller
{
    public function handle(
        Request $request,
        Appointment $appointment,
        BookingCalendar $calendar,
        AvailabilityService $availability,
        CustomerNotifier $notifier,
    ): View|RedirectResponse {
        // date/month mudam enquanto o cliente navega pelo calendário; o resto da URL precisa bater com a assinatura.
        abort_unless($request->hasValidSignatureWhileIgnoring(['date', 'month']), 403, 'Link de reagendamento inválido.');

        $appointment->load('product');
        $staff = $appointment->staff ?? Staff::primary();
        $signedUrl = $appointment->customerRescheduleUrl();

        if (! $appointment->canBeRescheduledByCustomer()) {
            return view('booking.reschedule', ['appointment' => $appointment, 'allowed' => false]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate(['starts_at' => ['required', 'date_format:Y-m-d H:i']]);
            $newStart = Carbon::createFromFormat('Y-m-d H:i', $data['starts_at']);
            $previousStart = $appointment->starts_at->copy();

            $moved = DB::transaction(function () use ($appointment, $staff, $newStart, $availability) {
                // Mesma trava do agendamento novo: dois pedidos para o mesmo horário passam um por vez.
                Staff::query()->whereKey($staff->id)->lockForUpdate()->first();

                if (! $availability->isAvailable($appointment->product, $staff, $newStart, $appointment->id)) {
                    return false;
                }

                $appointment->update([
                    'starts_at' => $newStart,
                    'ends_at' => $newStart->copy()->addMinutes($appointment->product->duration_minutes),
                ]);

                return true;
            });

            if (! $moved) {
                return redirect($signedUrl.'&date='.$newStart->toDateString())
                    ->with('error', 'Esse horário não está mais disponível. Escolha outro.');
            }

            if (! $previousStart->equalTo($newStart)) {
                $notifier->changed($appointment, AppointmentChanged::RESCHEDULED, $previousStart);
            }

            return redirect($signedUrl)
                ->with('status', 'Pronto! Seu atendimento foi reagendado para '.$newStart->translatedFormat('d/m/Y \à\s H:i').'. Enviamos a confirmação por e-mail.');
        }

        return view('booking.reschedule', [
            'appointment' => $appointment,
            'allowed' => true,
            'formUrl' => $signedUrl,
            'monthUrl' => fn ($m) => $signedUrl.'&month='.$m->format('Y-m'),
            'dayUrl' => fn ($d) => $signedUrl.'&date='.$d,
        ] + $calendar->build($appointment->product, $staff, $request->query('date'), $request->query('month'), $appointment->id));
    }
}
