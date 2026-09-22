<x-booking-layout>
    <h1 class="text-2xl font-semibold text-brand-slate">Agende seu atendimento para a emissão do certificado digital!</h1>
    <p class="mt-1 text-gray-600">Escolha o serviço, o horário e preencha seus dados abaixo.</p>

    @if ($products->isEmpty())
        <div class="mt-6 bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">Nenhum serviço disponível no momento.</div>
    @else
        {{-- 1. Serviço: muda os horários disponíveis, por isso recarrega a página. --}}
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @foreach ($products as $p)
                <a href="{{ route('home', ['product' => $p->id]) }}"
                   class="block rounded-lg border-l-4 p-4 transition {{ $p->id === $product->id ? 'border-brand-green bg-white shadow-sm' : 'border-gray-200 bg-white/60 hover:border-brand-blue' }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-semibold text-gray-900">{{ $p->name }}</span>
                        <span class="shrink-0 rounded-full bg-brand-blue-light px-2.5 py-0.5 text-xs font-medium text-brand-blue-dark">{{ $p->duration_minutes }} min</span>
                    </div>
                    @if ($p->description)<p class="mt-1 text-sm text-gray-600">{{ $p->description }}</p>@endif
                    @if ($p->price !== null)<p class="mt-1 text-sm text-gray-700">R$ {{ number_format($p->price, 2, ',', '.') }}</p>@endif
                </a>
            @endforeach
        </div>

        @if (! $hasAnyAvailability)
            <div class="mt-6 bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
                Não há horários disponíveis nos próximos {{ config('agenda.max_days_ahead') }} dias para esse serviço. Volte em breve.
            </div>
        @else
            {{-- 2. Dia: um calendário de mês normal, com seta pra trocar de mês. --}}
            <h2 class="mt-8 font-medium text-gray-800">1. Escolha o dia</h2>
            <div class="mt-3 bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    @if ($prevMonth)
                        <a href="{{ route('home', ['product' => $product->id, 'month' => $prevMonth->format('Y-m')]) }}"
                           class="w-10 h-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" aria-label="Mês anterior">&larr;</a>
                    @else
                        <span class="w-10 h-10 flex items-center justify-center text-gray-300">&larr;</span>
                    @endif

                    <span class="text-lg font-medium text-gray-800">{{ ucfirst($month->translatedFormat('F \d\e Y')) }}</span>

                    @if ($nextMonth)
                        <a href="{{ route('home', ['product' => $product->id, 'month' => $nextMonth->format('Y-m')]) }}"
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
                                    <a href="{{ route('home', ['product' => $product->id, 'date' => $cellDate]) }}"
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

            @if ($selectedDate === null)
                <div class="mt-6 bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                    Escolha um dia disponível no calendário acima.
                </div>
            @else
            {{-- 3. Horário + dados: tudo no mesmo formulário, sem precisar de outra página nem login. --}}
            <form method="POST" action="{{ route('booking.store') }}" enctype="multipart/form-data" class="mt-8 space-y-5"
                  x-data="{ method: '{{ old('validation_method', 'presencial') }}' }">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div>
                    <h2 class="font-medium text-gray-800">2. Escolha o horário <span class="font-normal text-gray-500">· {{ \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F') }}</span></h2>
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

                <h2 class="pt-2 font-medium text-gray-800">3. Seus dados</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="holder_document" value="CNPJ ou CPF" />
                        <x-text-input id="holder_document" name="holder_document" type="text" inputmode="numeric" maxlength="18"
                                      class="block mt-1 w-full"
                                      oninput="VVS.maskDocument(this)" onblur="VVS.validateDocument(this)"
                                      :value="old('holder_document')" required />
                        <p id="holder_document-client-error" class="hidden mt-2 text-sm text-red-600"></p>
                        <x-input-error :messages="$errors->get('holder_document')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="holder_phone" value="Telefone ou Celular" />
                        <x-text-input id="holder_phone" name="holder_phone" type="text" inputmode="numeric" maxlength="15"
                                      class="block mt-1 w-full"
                                      oninput="VVS.maskPhone(this)" :value="old('holder_phone')" required />
                        <x-input-error :messages="$errors->get('holder_phone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="holder_name" value="Nome ou Razão Social" />
                    <x-text-input id="holder_name" name="holder_name" type="text" class="block mt-1 w-full"
                                  :value="old('holder_name')" required />
                    <x-input-error :messages="$errors->get('holder_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="holder_email" value="E-mail" />
                    <x-text-input id="holder_email" name="holder_email" type="email" class="block mt-1 w-full"
                                  :value="old('holder_email')" required />
                    <x-input-error :messages="$errors->get('holder_email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="accountant_name" value="Contador (opcional)" />
                    <x-text-input id="accountant_name" name="accountant_name" type="text" class="block mt-1 w-full"
                                  :value="old('accountant_name')" />
                    <x-input-error :messages="$errors->get('accountant_name')" class="mt-2" />
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">Forma de validação</legend>
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="validation_method" value="presencial" x-model="method"
                                   class="text-brand-blue focus:ring-brand-blue" required>
                            <span class="ms-2 text-sm text-gray-700">Presencial</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="validation_method" value="videoconferencia" x-model="method"
                                   class="text-brand-blue focus:ring-brand-blue" required>
                            <span class="ms-2 text-sm text-gray-700">Videoconferência</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('validation_method')" class="mt-2" />
                </fieldset>

                <div x-show="method === 'videoconferencia'" x-cloak>
                    <x-input-label for="document" value="Foto da CNH, frente e verso (opcional agora, obrigatória até a videoconferência)" />
                    <input id="document" name="document" type="file" accept=".jpg,.jpeg,.png,.pdf"
                           class="block mt-1 w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-slate file:px-3 file:py-1.5 file:text-white hover:file:bg-brand-blue">
                    <p class="mt-1 text-xs text-gray-500">Pode enviar depois pelo WhatsApp (12) 3600-5110.</p>
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                </div>

                @include('booking.partials.terms')

                <label class="flex items-start gap-2">
                    <input type="checkbox" name="terms" value="1" required
                           class="mt-0.5 rounded border-gray-300 text-brand-blue focus:ring-brand-blue">
                    <span class="text-sm text-gray-700">Li e estou de acordo com as informações acima.</span>
                </label>
                <x-input-error :messages="$errors->get('terms')" class="mt-2" />

                <div>
                    <x-input-label for="notes" value="Observações (opcional)" />
                    <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                @if (config('recaptcha.site_key'))
                    <div>
                        <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
                        <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2" />
                    </div>
                @endif

                <div class="flex justify-end">
                    <x-primary-button>Confirmar agendamento</x-primary-button>
                </div>
            </form>
            @endif
        @endif
    @endif

    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endpush
</x-booking-layout>
