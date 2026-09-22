<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Horários de atendimento</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                @foreach (\App\Models\AvailabilityRule::WEEKDAYS as $day => $label)
                    <div class="px-6 py-3 flex items-start justify-between gap-4">
                        <div class="w-40 text-sm font-medium text-gray-800 pt-1">{{ $label }}</div>
                        <div class="flex-1 flex flex-wrap gap-2 justify-end">
                            @forelse ($rulesByDay->get($day, collect()) as $rule)
                                <form method="POST" action="{{ route('admin.availability.destroy', $rule) }}" class="inline-flex items-center rounded-full bg-brand-blue-light text-brand-blue-dark text-sm ps-3 pe-1 py-1">
                                    @csrf @method('DELETE')
                                    {{ substr($rule->start_time, 0, 5) }} – {{ substr($rule->end_time, 0, 5) }}
                                    <button class="ms-2 h-5 w-5 rounded-full hover:bg-brand-blue/20 leading-none" title="Remover" aria-label="Remover horário">&times;</button>
                                </form>
                            @empty
                                <span class="text-sm text-gray-400 pt-1">Fechado</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.availability.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                <div class="font-medium text-gray-800 mb-1">Adicionar horário</div>
                <p class="text-sm text-gray-500 mb-4">Para intervalo de almoço, cadastre duas faixas no mesmo dia (ex.: 09:00–12:00 e 13:00–18:00).</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <x-input-label for="weekday" value="Dia" />
                        <select id="weekday" name="weekday" class="block mt-1 w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm">
                            @foreach (\App\Models\AvailabilityRule::WEEKDAYS as $day => $label)
                                <option value="{{ $day }}" @selected(old('weekday') == $day)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('weekday')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="start_time" value="Início" />
                        <x-text-input id="start_time" name="start_time" type="time" class="block mt-1 w-full" :value="old('start_time', '09:00')" required />
                        <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="end_time" value="Fim" />
                        <x-text-input id="end_time" name="end_time" type="time" class="block mt-1 w-full" :value="old('end_time', '18:00')" required />
                        <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                    </div>
                    <x-primary-button class="justify-center">Adicionar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
