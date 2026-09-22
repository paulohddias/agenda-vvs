<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configurações</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="min_notice_minutes" value="Antecedência mínima para agendar (minutos)" />
                    <p class="text-sm text-gray-500 mt-0.5">O cliente não consegue marcar um horário com menos do que isso de antecedência. Ex.: 10 = não dá para marcar às 15h se já passou das 14h50.</p>
                    <x-text-input id="min_notice_minutes" name="min_notice_minutes" type="number" min="0" max="1440" class="block mt-1 w-40" :value="old('min_notice_minutes', $minNoticeMinutes)" required />
                    <x-input-error :messages="$errors->get('min_notice_minutes')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="slot_step_minutes" value="Intervalo entre horários oferecidos (minutos)" />
                    <p class="text-sm text-gray-500 mt-0.5">Ex.: 30 = os horários aparecem de 30 em 30 minutos (09:00, 09:30, 10:00...).</p>
                    <x-text-input id="slot_step_minutes" name="slot_step_minutes" type="number" min="5" max="240" class="block mt-1 w-40" :value="old('slot_step_minutes', $slotStepMinutes)" required />
                    <x-input-error :messages="$errors->get('slot_step_minutes')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="max_days_ahead" value="Quantos dias à frente dá para agendar" />
                    <x-text-input id="max_days_ahead" name="max_days_ahead" type="number" min="1" max="365" class="block mt-1 w-40" :value="old('max_days_ahead', $maxDaysAhead)" required />
                    <x-input-error :messages="$errors->get('max_days_ahead')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="cancel_min_hours" value="Prazo mínimo para o cliente cancelar sozinho (horas)" />
                    <x-text-input id="cancel_min_hours" name="cancel_min_hours" type="number" min="0" max="168" class="block mt-1 w-40" :value="old('cancel_min_hours', $cancelMinHours)" required />
                    <x-input-error :messages="$errors->get('cancel_min_hours')" class="mt-2" />
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button>Salvar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
