<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bloqueios de agenda</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="POST" action="{{ route('admin.blocked.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6"
                  x-data="{ dayMode: '{{ old('day_mode', 'full') }}' }">
                @csrf
                <div class="font-medium text-gray-800 mb-1">Novo bloqueio</div>
                <p class="text-sm text-gray-500 mb-4">Feriados, folgas ou qualquer período em que não haverá atendimento.</p>

                <fieldset class="mb-4">
                    <div class="flex gap-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="day_mode" value="full" x-model="dayMode" class="text-brand-blue focus:ring-brand-blue">
                            <span class="ms-2 text-sm text-gray-700">Dia inteiro</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="day_mode" value="partial" x-model="dayMode" class="text-brand-blue focus:ring-brand-blue">
                            <span class="ms-2 text-sm text-gray-700">Horário específico</span>
                        </label>
                    </div>
                </fieldset>

                {{-- Dia inteiro: escolhe as datas no calendário nativo; dá pra bloquear vários dias seguidos (ex.: recesso). --}}
                <div x-show="dayMode === 'full'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="date_start" value="De" />
                        <x-text-input id="date_start" name="date_start" type="date" class="block mt-1 w-full" :value="old('date_start')" />
                        <x-input-error :messages="$errors->get('date_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="date_end" value="Até (opcional, para vários dias)" />
                        <x-text-input id="date_end" name="date_end" type="date" class="block mt-1 w-full" :value="old('date_end')" />
                        <x-input-error :messages="$errors->get('date_end')" class="mt-2" />
                    </div>
                </div>

                {{-- Horário específico: um só dia, com hora de início e fim (ex.: almoço, uma tarde). --}}
                <div x-show="dayMode === 'partial'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="date" value="Dia" />
                        <x-text-input id="date" name="date" type="date" class="block mt-1 w-full" :value="old('date')" />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="time_start" value="Início" />
                        <x-text-input id="time_start" name="time_start" type="time" class="block mt-1 w-full" :value="old('time_start')" />
                        <x-input-error :messages="$errors->get('time_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="time_end" value="Fim" />
                        <x-text-input id="time_end" name="time_end" type="time" class="block mt-1 w-full" :value="old('time_end')" />
                        <x-input-error :messages="$errors->get('time_end')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="reason" value="Motivo (opcional)" />
                    <x-text-input id="reason" name="reason" type="text" class="block mt-1 w-full" :value="old('reason')" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>

                <div class="mt-4 flex justify-end"><x-primary-button>Bloquear</x-primary-button></div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                <div class="px-6 py-4 font-medium text-gray-800">Bloqueios ativos e futuros</div>
                @forelse ($periods as $period)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div>
                            @if ($period->isFullDay())
                                <div class="text-sm text-gray-900">
                                    Dia inteiro · {{ $period->starts_at->format('d/m/Y') }}
                                    @unless ($period->starts_at->isSameDay($period->ends_at))
                                        até {{ $period->ends_at->format('d/m/Y') }}
                                    @endunless
                                </div>
                            @else
                                <div class="text-sm text-gray-900">{{ $period->starts_at->format('d/m/Y H:i') }} até {{ $period->ends_at->format('d/m/Y H:i') }}</div>
                            @endif
                            @if ($period->reason)<div class="text-sm text-gray-500">{{ $period->reason }}</div>@endif
                        </div>
                        <form method="POST" action="{{ route('admin.blocked.destroy', $period) }}" onsubmit="return confirm('Remover este bloqueio?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-600 hover:underline">Remover</button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500">Nenhum bloqueio cadastrado.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
