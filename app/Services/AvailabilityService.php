<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Product;
use App\Models\Staff;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula os horários livres: faixas de atendimento do atendente,
 * menos bloqueios, menos agendamentos já feitos, respeitando a duração do produto.
 * Nada é gravado; os horários livres são sempre derivados.
 */
class AvailabilityService
{
    /**
     * @return array<string, Collection<int, Carbon>> horários de início livres, por dia (Y-m-d).
     *                                                 Dias sem horário aparecem com coleção vazia.
     */
    public function slots(Product $product, Staff $staff, CarbonInterface $from, CarbonInterface $to, ?int $excludeAppointmentId = null): array
    {
        $rangeStart = Carbon::instance($from)->startOfDay();
        $rangeEnd = Carbon::instance($to)->endOfDay();

        $rulesByWeekday = $staff->availabilityRules()->orderBy('start_time')->get()->groupBy('weekday');

        $blocked = BlockedPeriod::query()
            ->where(fn ($q) => $q->whereNull('staff_id')->orWhere('staff_id', $staff->id))
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            ->get(['starts_at', 'ends_at']);

        $taken = Appointment::blocking()
            ->where('staff_id', $staff->id)
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            // Reagendar não deve trombar com o próprio horário antigo do mesmo agendamento.
            ->when($excludeAppointmentId, fn ($q, $id) => $q->whereKeyNot($id))
            ->get(['starts_at', 'ends_at']);

        $busy = $blocked->concat($taken);
        $earliest = now()->addMinutes(config('agenda.min_notice_minutes'));
        $duration = $product->duration_minutes;
        $step = max(5, (int) config('agenda.slot_step_minutes'));

        $result = [];

        foreach (CarbonPeriod::create($rangeStart, $rangeEnd->copy()->startOfDay()) as $day) {
            $day = Carbon::instance($day);
            $slots = collect();

            foreach ($rulesByWeekday->get($day->dayOfWeek, []) as $rule) {
                $cursor = $day->copy()->setTimeFromTimeString($rule->start_time);
                $close = $day->copy()->setTimeFromTimeString($rule->end_time);

                while ($cursor->copy()->addMinutes($duration)->lte($close)) {
                    $slotEnd = $cursor->copy()->addMinutes($duration);

                    // Estrito: faltando exatamente o mínimo (ex.: 10min) já não vale mais, tem que sobrar mais que isso.
                    if ($cursor->gt($earliest) && ! $this->overlapsAny($busy, $cursor, $slotEnd)) {
                        $slots->push($cursor->copy());
                    }

                    $cursor->addMinutes($step);
                }
            }

            $result[$day->toDateString()] = $slots;
        }

        return $result;
    }

    public function isAvailable(Product $product, Staff $staff, CarbonInterface $start, ?int $excludeAppointmentId = null): bool
    {
        $slots = $this->slots($product, $staff, $start, $start, $excludeAppointmentId)[$start->toDateString()] ?? collect();

        return $slots->contains(fn (Carbon $slot) => $slot->equalTo($start));
    }

    private function overlapsAny(Collection $periods, Carbon $start, Carbon $end): bool
    {
        return $periods->contains(fn ($p) => $p->starts_at->lt($end) && $p->ends_at->gt($start));
    }
}
