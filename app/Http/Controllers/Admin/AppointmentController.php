<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentDetailsRequest;
use App\Mail\AppointmentChanged;
use App\Models\Appointment;
use App\Models\Staff;
use App\Services\AvailabilityService;
use App\Services\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    /** Pixels por minuto na agenda de dia/semana — define a altura de cada bloco. */
    private const PX_PER_MINUTE = 1.2;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'day' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUS_LABELS))],
        ]);

        $view = $filters['view'] ?? 'week';
        $day = isset($filters['day']) ? Carbon::parse($filters['day']) : today();
        $status = $filters['status'] ?? null;

        $weekStart = $day->copy()->startOfWeek(Carbon::SUNDAY);

        $data = match ($view) {
            'week' => $this->buildTimeline($weekStart, 7, $status),
            'month' => $this->buildMonth($day, $status),
            default => $this->buildTimeline($day->copy(), 1, $status),
        };

        [$prevDay, $nextDay, $rangeLabel] = match ($view) {
            'week' => [
                $day->copy()->subWeek(),
                $day->copy()->addWeek(),
                ucfirst($weekStart->translatedFormat('d \d\e F')).' – '.$weekStart->copy()->addDays(6)->translatedFormat('d \d\e F'),
            ],
            'month' => [
                $day->copy()->subMonthNoOverflow(),
                $day->copy()->addMonthNoOverflow(),
                ucfirst($day->translatedFormat('F \d\e Y')),
            ],
            default => [
                $day->copy()->subDay(),
                $day->copy()->addDay(),
                ucfirst($day->translatedFormat('l, d \d\e F')),
            ],
        };

        return view('admin.appointments.index', array_merge($data, [
            'view' => $view,
            'day' => $day,
            'status' => $status,
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
            'rangeLabel' => $rangeLabel,
        ]));
    }

    /**
     * Monta a grade de horas compartilhada por 1 dia (visão "Dia") ou 7 dias (visão "Semana").
     */
    private function buildTimeline(Carbon $firstDay, int $numDays, ?string $status): array
    {
        $days = collect(range(0, $numDays - 1))->map(fn ($i) => $firstDay->copy()->addDays($i));

        $appointments = Appointment::with('product')
            ->whereBetween('starts_at', [$days->first()->copy()->startOfDay(), $days->last()->copy()->endOfDay()])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $a) => $a->starts_at->toDateString());

        $staff = Staff::primary();
        $rules = $staff->availabilityRules()
            ->whereIn('weekday', $days->map(fn ($d) => $d->dayOfWeek)->unique())
            ->get();

        [$startHour, $endHour] = $this->hourBounds($rules, $appointments->flatten());

        $hours = [];
        for ($h = $startHour; $h <= $endHour; $h++) {
            $hours[] = ['label' => sprintf('%02d:00', $h), 'top' => ($h - $startHour) * 60 * self::PX_PER_MINUTE];
        }

        $now = now();

        $columns = $days->map(function (Carbon $d) use ($appointments, $startHour, $endHour, $now) {
            $dayStart = $d->copy()->setTime($startHour, 0);
            $dayEnd = $d->copy()->setTime($endHour, 0);

            $blocks = $appointments->get($d->toDateString(), collect())->map(fn (Appointment $a) => [
                'appointment' => $a,
                'top' => $dayStart->diffInMinutes($a->starts_at) * self::PX_PER_MINUTE,
                'height' => max(20, $a->starts_at->diffInMinutes($a->ends_at) * self::PX_PER_MINUTE),
            ]);

            return [
                'date' => $d,
                'blocks' => $blocks,
                'nowOffset' => $d->isToday() && $now->between($dayStart, $dayEnd)
                    ? $dayStart->diffInMinutes($now) * self::PX_PER_MINUTE
                    : null,
            ];
        });

        return [
            'days' => $columns,
            'hours' => $hours,
            'totalHeight' => ($endHour - $startHour) * 60 * self::PX_PER_MINUTE,
            'hasSchedule' => $rules->isNotEmpty() || $appointments->isNotEmpty(),
        ];
    }

    /** @return array{0: int, 1: int} horas de início e fim da grade */
    private function hourBounds(Collection $rules, Collection $appointments): array
    {
        $starts = $rules->pluck('start_time')->merge($appointments->map(fn ($a) => $a->starts_at->format('H:i:s')));
        $ends = $rules->pluck('end_time')->merge($appointments->map(fn ($a) => $a->ends_at->format('H:i:s')));

        if ($starts->isEmpty()) {
            return [8, 19];
        }

        $latest = Carbon::parse($ends->max());

        return [
            Carbon::parse($starts->min())->hour,
            $latest->minute > 0 || $latest->second > 0 ? $latest->hour + 1 : $latest->hour,
        ];
    }

    private function buildMonth(Carbon $anchor, ?string $status): array
    {
        $month = $anchor->copy()->startOfMonth();

        $appointments = Appointment::with('product')
            ->whereBetween('starts_at', [$month->copy()->startOfDay(), $month->copy()->endOfMonth()->endOfDay()])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $a) => $a->starts_at->toDateString());

        $cells = array_fill(0, $month->dayOfWeek, null);

        for ($d = 1; $d <= $month->daysInMonth; $d++) {
            $date = $month->copy()->setDay($d);
            $cells[] = ['date' => $date, 'appointments' => $appointments->get($date->toDateString(), collect())];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return ['monthWeeks' => array_chunk($cells, 7), 'month' => $month];
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load('product');

        return view('admin.appointments.show', ['appointment' => $appointment]);
    }

    public function edit(Appointment $appointment): View
    {
        $appointment->load('product');

        return view('admin.appointments.edit', ['appointment' => $appointment]);
    }

    /** Corrige os dados do cliente. Data e horário mudam pelo reagendamento, não aqui. */
    public function updateDetails(UpdateAppointmentDetailsRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->safe()->except('document');

        if ($request->hasFile('document')) {
            $oldPath = $appointment->document_path;
            $data['document_path'] = $request->file('document')->store('appointment-documents', 'local');

            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        $appointment->update($data);

        return redirect()
            ->route('admin.appointments.index', ['view' => 'week', 'day' => $appointment->starts_at->toDateString()])
            ->with('status', 'Dados do agendamento de '.$appointment->holder_name.' atualizados.');
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Appointment::STATUS_LABELS))],
        ]);

        $previousStatus = $appointment->status;
        $appointment->update($data);

        // Só avisa o cliente de mudanças que interessam a ele, e só se o status mudou de fato.
        $change = match ($appointment->status) {
            Appointment::STATUS_CONFIRMED => AppointmentChanged::CONFIRMED,
            Appointment::STATUS_CANCELLED => AppointmentChanged::CANCELLED,
            default => null,
        };

        if ($change && $appointment->status !== $previousStatus) {
            $this->notifyCustomer($appointment, $change);
        }

        return back()->with('status', 'Agendamento marcado como '.mb_strtolower($appointment->statusLabel()).'.');
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'reschedule_date' => ['required', 'date'],
            'reschedule_time' => ['required', 'date_format:H:i'],
        ]);

        $newStart = Carbon::parse($data['reschedule_date'].' '.$data['reschedule_time']);
        $appointment->loadMissing('product');
        $staff = $appointment->staff ?? Staff::primary();

        if (! app(AvailabilityService::class)->isAvailable($appointment->product, $staff, $newStart, $appointment->id)) {
            return back()->with('error', 'Esse novo horário não está disponível.');
        }

        $previousStart = $appointment->starts_at->copy();

        $appointment->update([
            'starts_at' => $newStart,
            'ends_at' => $newStart->copy()->addMinutes($appointment->product->duration_minutes),
        ]);

        if (! $previousStart->equalTo($newStart)) {
            $this->notifyCustomer($appointment, AppointmentChanged::RESCHEDULED, $previousStart);
        }

        return back()->with('status', 'Agendamento reagendado para '.$newStart->translatedFormat('d/m/Y \à\s H:i').'.');
    }

    private function notifyCustomer(Appointment $appointment, string $change, ?Carbon $previousStart = null): void
    {
        app(CustomerNotifier::class)->changed($appointment, $change, $previousStart);
    }

    public function downloadDocument(Appointment $appointment): StreamedResponse
    {
        abort_unless($appointment->document_path, 404);

        return Storage::disk('local')->download($appointment->document_path);
    }
}
