<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Staff;
use Illuminate\Support\Carbon;

/**
 * Calendário de mês + horários do dia escolhido, usado pela página de agendamento
 * e pela de reagendamento do cliente.
 */
class BookingCalendar
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * @param  string|null  $requestedDate  ?date=Y-m-d (dia clicado)
     * @param  string|null  $requestedMonth  ?month=Y-m (navegando pelas setas)
     * @param  int|null  $excludeAppointmentId  no reagendamento, o próprio horário do cliente não conta como ocupado
     */
    public function build(Product $product, Staff $staff, mixed $requestedDate, mixed $requestedMonth, ?int $excludeAppointmentId = null): array
    {
        $today = today();
        $maxDate = today()->addDays(config('agenda.max_days_ahead'));

        $slotsByDay = $this->availability->slots($product, $staff, $today, $maxDate, $excludeAppointmentId);
        $availableDays = array_filter($slotsByDay, fn ($slots) => $slots->isNotEmpty());

        // O mês mostrado nunca fica fora do intervalo em que dá para agendar.
        $earliestMonth = $today->copy()->startOfMonth();
        $latestMonth = $maxDate->copy()->startOfMonth();

        if (is_string($requestedDate) && isset($availableDays[$requestedDate])) {
            // Veio de um link de dia (ou de um agendamento que falhou): mostra o mês desse dia.
            $selectedDate = $requestedDate;
            $month = Carbon::parse($requestedDate)->startOfMonth();
        } elseif (is_string($requestedMonth) && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
            // Navegou pelo calendário sem escolher dia ainda.
            $selectedDate = null;
            $month = Carbon::createFromFormat('Y-m-d', $requestedMonth.'-01')->startOfMonth();
        } else {
            // Primeira visita: cai no mês do primeiro dia livre.
            $selectedDate = array_key_first($availableDays);
            $month = $selectedDate ? Carbon::parse($selectedDate)->startOfMonth() : $earliestMonth->copy();
        }

        $month = $month->max($earliestMonth)->min($latestMonth);

        return [
            'month' => $month,
            'prevMonth' => $month->gt($earliestMonth) ? $month->copy()->subMonth() : null,
            'nextMonth' => $month->lt($latestMonth) ? $month->copy()->addMonth() : null,
            'calendarWeeks' => $this->weeks($month, $availableDays, $today, $maxDate),
            'hasAnyAvailability' => ! empty($availableDays),
            'selectedDate' => $selectedDate,
            'times' => $selectedDate ? $availableDays[$selectedDate] : collect(),
        ];
    }

    /**
     * Semanas (domingo a sábado) do mês, com cada dia marcado como disponível ou não.
     * Dias fora do mês vêm como null, só para preencher a grade.
     *
     * @return list<list<array{date: Carbon, available: bool}|null>>
     */
    private function weeks(Carbon $month, array $availableDays, Carbon $today, Carbon $maxDate): array
    {
        $cells = array_fill(0, $month->dayOfWeek, null);

        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->setDay($day);
            $cells[] = [
                'date' => $date,
                'available' => $date->between($today, $maxDate) && isset($availableDays[$date->toDateString()]),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }
}
