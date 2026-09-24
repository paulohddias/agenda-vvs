{{-- Calendário de mês. Quem inclui passa $monthUrl(Carbon $month) e $dayUrl(string $date) para montar os links. --}}
<div class="mt-3 bg-white rounded-lg shadow-sm p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
        @if ($prevMonth)
            <a href="{{ $monthUrl($prevMonth) }}"
               class="w-10 h-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" aria-label="Mês anterior">&larr;</a>
        @else
            <span class="w-10 h-10 flex items-center justify-center text-gray-300">&larr;</span>
        @endif

        <span class="text-lg font-medium text-gray-800">{{ ucfirst($month->translatedFormat('F \d\e Y')) }}</span>

        @if ($nextMonth)
            <a href="{{ $monthUrl($nextMonth) }}"
               class="w-10 h-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" aria-label="Próximo mês">&rarr;</a>
        @else
            <span class="w-10 h-10 flex items-center justify-center text-gray-300">&rarr;</span>
        @endif
    </div>

    <div class="grid grid-cols-7 text-center text-sm text-gray-400 mb-2">
        @foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $weekdayLetter)
            <div>{{ $weekdayLetter }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-y-2">
        @foreach ($calendarWeeks as $week)
            @foreach ($week as $cell)
                @if ($cell === null)
                    <div></div>
                @elseif ($cell['available'])
                    @php $cellDate = $cell['date']->toDateString(); @endphp
                    <div class="flex justify-center">
                        <a href="{{ $dayUrl($cellDate) }}"
                           class="w-9 h-9 sm:w-11 sm:h-11 flex items-center justify-center rounded-full font-medium transition {{ $cellDate === $selectedDate ? 'bg-brand-slate text-white' : 'text-gray-800 hover:bg-brand-green-light' }}">
                            {{ $cell['date']->day }}
                        </a>
                    </div>
                @else
                    <div class="flex justify-center">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 flex items-center justify-center text-gray-300">{{ $cell['date']->day }}</div>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>
