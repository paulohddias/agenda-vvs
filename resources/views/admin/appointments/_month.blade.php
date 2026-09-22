@php $maxChips = 3; @endphp

<div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
    <div class="grid grid-cols-7 text-center text-sm text-gray-400 mb-2">
        @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekdayLabel)
            <div>{{ $weekdayLabel }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @foreach ($monthWeeks as $week)
            @foreach ($week as $cell)
                @if ($cell === null)
                    <div></div>
                @else
                    <div class="min-h-[6.5rem] rounded-md p-1 {{ $cell['date']->isToday() ? 'bg-brand-blue-light' : '' }}">
                        <div class="text-xs {{ $cell['date']->isToday() ? 'font-semibold text-brand-blue-dark' : 'text-gray-500' }}">{{ $cell['date']->day }}</div>

                        <div class="space-y-0.5 mt-0.5">
                            @foreach ($cell['appointments']->take($maxChips) as $appointment)
                                @php($color = match ($appointment->status) {
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-50 text-red-500 line-through opacity-70',
                                    'completed' => 'bg-gray-100 text-gray-500',
                                    default => 'bg-yellow-100 text-yellow-800',
                                })
                                <button type="button" @click="show(JSON.parse($el.dataset.appt))" data-appt='@json($appointment->toCalendarPayload())'
                                        class="block w-full truncate rounded px-1 py-0.5 text-left text-[11px] {{ $color }}">
                                    {{ $appointment->starts_at->format('H:i') }} {{ $appointment->holder_name }}
                                </button>
                            @endforeach

                            @if ($cell['appointments']->count() > $maxChips)
                                <a href="{{ route('admin.appointments.index', ['view' => 'day', 'day' => $cell['date']->toDateString(), 'status' => $status]) }}"
                                   class="block text-[11px] text-brand-blue hover:underline">
                                    +{{ $cell['appointments']->count() - $maxChips }} mais
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>
