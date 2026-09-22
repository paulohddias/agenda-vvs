@if (! $hasSchedule)
    <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">Sem expediente cadastrado nesse período.</div>
@else
    <div class="bg-white shadow-sm sm:rounded-lg p-4 overflow-x-auto">
        <div class="flex" style="min-width: {{ count($days) > 1 ? 700 : 500 }}px;">
            {{-- Coluna das horas --}}
            <div class="relative w-14 shrink-0" style="height: {{ $totalHeight }}px;">
                @foreach ($hours as $hour)
                    <div class="absolute left-0 -translate-y-1/2 text-xs text-gray-400" style="top: {{ $hour['top'] }}px;">{{ $hour['label'] }}</div>
                @endforeach
            </div>

            {{-- Uma coluna por dia (1 na visão Dia, 7 na visão Semana) --}}
            @foreach ($days as $column)
                <div class="flex-1 min-w-0">
                    @if (count($days) > 1)
                        <div class="text-center text-xs font-medium mb-1 {{ $column['date']->isToday() ? 'text-brand-blue' : 'text-gray-500' }}">
                            {{ ucfirst($column['date']->translatedFormat('D')) }} {{ $column['date']->format('d/m') }}
                        </div>
                    @endif

                    <div class="relative border-l border-gray-200" style="height: {{ $totalHeight }}px;">
                        @foreach ($hours as $hour)
                            <div class="absolute left-0 right-0 border-t border-gray-100" style="top: {{ $hour['top'] }}px;"></div>
                        @endforeach

                        @if ($column['nowOffset'] !== null)
                            <div class="absolute left-0 right-0 z-20 flex items-center gap-1" style="top: {{ $column['nowOffset'] }}px;">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 -ms-0.5"></span>
                                <div class="flex-1 border-t-2 border-red-500"></div>
                            </div>
                        @endif

                        @foreach ($column['blocks'] as $block)
                            @php $appointment = $block['appointment']; @endphp
                            @php($color = match ($appointment->status) {
                                'confirmed' => 'bg-green-100 border-green-500 text-green-900',
                                'cancelled' => 'bg-red-50 border-red-300 text-red-700 line-through opacity-70',
                                'completed' => 'bg-gray-100 border-gray-400 text-gray-600',
                                default => 'bg-yellow-100 border-yellow-500 text-yellow-900',
                            })
                            <button type="button" @click="show(JSON.parse($el.dataset.appt))" data-appt='@json($appointment->toCalendarPayload())'
                               class="absolute left-1 right-1 rounded-md border-l-4 px-2 py-1 text-xs text-left overflow-hidden hover:shadow-md transition {{ $color }}"
                               style="top: {{ $block['top'] }}px; height: {{ $block['height'] }}px;">
                                <span class="font-semibold">{{ $appointment->starts_at->format('H:i') }} {{ $appointment->holder_name }}</span>
                                <span class="block truncate">{{ $appointment->product->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($days->every(fn ($c) => $c['blocks']->isEmpty()))
        <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">Nenhum agendamento nesse período.</div>
    @endif
@endif
