<x-booking-layout>
    <h1 class="text-2xl font-semibold text-brand-slate">Reagendar atendimento</h1>

    <div class="mt-4 bg-white rounded-lg shadow-sm p-4 sm:p-5 text-sm text-gray-700">
        <div class="font-semibold text-gray-900">{{ $appointment->holder_name }}</div>
        <div class="mt-1">{{ $appointment->product->name }} · {{ $appointment->validationMethodLabel() }}</div>
        <div class="mt-1">
            Horário atual:
            <span class="font-medium text-gray-900">{{ $appointment->starts_at->translatedFormat('d/m/Y (l) \à\s H:i') }}</span>
        </div>
    </div>

    @if (! $allowed)
        <div class="mt-6 bg-white rounded-lg shadow-sm p-8 text-center text-gray-600">
            @if (in_array($appointment->status, [\App\Models\Appointment::STATUS_PENDING, \App\Models\Appointment::STATUS_CONFIRMED], true))
                Não dá mais para reagendar pelo link: faltam menos de {{ config('agenda.cancel_min_hours') }} horas para o atendimento.
            @else
                Este agendamento está {{ mb_strtolower($appointment->statusLabel()) }} e não pode ser reagendado pelo link.
            @endif
            <p class="mt-3">Fale com a gente pelo WhatsApp <strong>(12) 3600-5110</strong>.</p>
        </div>
    @elseif (! $hasAnyAvailability)
        <div class="mt-6 bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
            Não há outros horários disponíveis nos próximos {{ config('agenda.max_days_ahead') }} dias.
            Fale com a gente pelo WhatsApp <strong>(12) 3600-5110</strong>.
        </div>
    @else
        <h2 class="mt-8 font-medium text-gray-800">1. Escolha o novo dia</h2>
        @include('booking.partials.calendar')

        @if ($selectedDate === null)
            <div class="mt-6 bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                Escolha um dia disponível no calendário acima.
            </div>
        @else
            <form method="POST" action="{{ $formUrl }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <h2 class="font-medium text-gray-800">2. Escolha o novo horário <span class="font-normal text-gray-500">· {{ \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F') }}</span></h2>
                    <div class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-2">
                        @foreach ($times as $time)
                            @php $value = $time->format('Y-m-d H:i'); @endphp
                            <label>
                                <input type="radio" name="starts_at" value="{{ $value }}" class="peer sr-only" required
                                       @checked(old('starts_at') === $value)>
                                <span class="block rounded-lg border border-gray-200 bg-white py-2 text-center font-medium text-gray-800 cursor-pointer
                                             peer-checked:border-brand-slate peer-checked:bg-brand-slate peer-checked:text-white
                                             hover:border-brand-green">
                                    {{ $time->format('H:i') }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Confirmar novo horário</x-primary-button>
                </div>
            </form>
        @endif
    @endif
</x-booking-layout>
