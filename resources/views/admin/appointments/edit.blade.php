<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar dados do agendamento</h2>
            <x-status-badge :appointment="$appointment" />
        </div>
    </x-slot>

    @php
        $backUrl = route('admin.appointments.index', ['view' => 'week', 'day' => $appointment->starts_at->toDateString()]);
    @endphp

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-700">
                <span class="font-medium text-gray-900">{{ $appointment->product->name }}</span>
                · {{ $appointment->starts_at->translatedFormat('d/m/Y (D) H:i') }} – {{ $appointment->ends_at->format('H:i') }}
                <p class="mt-1 text-xs text-gray-500">Para mudar data ou horário, use "Reagendar" na agenda — assim o cliente recebe o aviso por e-mail.</p>
            </div>

            <form method="POST" action="{{ route('admin.appointments.update-details', $appointment) }}" enctype="multipart/form-data"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4"
                  x-data="{ method: @js(old('validation_method', $appointment->validation_method)) }">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="holder_document" value="CNPJ ou CPF" />
                        <x-text-input id="holder_document" name="holder_document" type="text" inputmode="numeric" maxlength="18"
                                      class="block mt-1 w-full" oninput="VVS.maskDocument(this)"
                                      :value="old('holder_document', $appointment->holderDocumentFormatted())" required />
                        <x-input-error :messages="$errors->get('holder_document')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="holder_phone" value="Telefone ou Celular" />
                        <x-text-input id="holder_phone" name="holder_phone" type="text" inputmode="numeric" maxlength="15"
                                      class="block mt-1 w-full" oninput="VVS.maskPhone(this)"
                                      :value="old('holder_phone', $appointment->holderPhoneFormatted())" required />
                        <x-input-error :messages="$errors->get('holder_phone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="holder_name" value="Nome ou Razão Social" />
                    <x-text-input id="holder_name" name="holder_name" type="text" class="block mt-1 w-full"
                                  :value="old('holder_name', $appointment->holder_name)" required />
                    <x-input-error :messages="$errors->get('holder_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="holder_email" value="E-mail" />
                    <x-text-input id="holder_email" name="holder_email" type="email" class="block mt-1 w-full"
                                  :value="old('holder_email', $appointment->holder_email)" required />
                    <x-input-error :messages="$errors->get('holder_email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="accountant_name" value="Contador (opcional)" />
                    <x-text-input id="accountant_name" name="accountant_name" type="text" class="block mt-1 w-full"
                                  :value="old('accountant_name', $appointment->accountant_name)" />
                    <x-input-error :messages="$errors->get('accountant_name')" class="mt-2" />
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">Forma de validação</legend>
                    <div class="mt-2 flex gap-6">
                        @foreach (\App\Models\Appointment::VALIDATION_METHOD_LABELS as $value => $label)
                            <label class="inline-flex items-center">
                                <input type="radio" name="validation_method" value="{{ $value }}" x-model="method"
                                       class="text-brand-blue focus:ring-brand-blue" required>
                                <span class="ms-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('validation_method')" class="mt-2" />
                </fieldset>

                <div>
                    <x-input-label for="document" value="CNH / documento (opcional)" />
                    @if ($appointment->document_path)
                        <p class="mt-1 text-sm text-gray-600">
                            Já existe um documento enviado —
                            <a href="{{ route('admin.appointments.document', $appointment) }}" class="text-brand-blue hover:underline">baixar</a>.
                            Enviar outro substitui o atual.
                        </p>
                    @endif
                    <input id="document" name="document" type="file" accept=".jpg,.jpeg,.png,.pdf"
                           class="block mt-1 w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-slate file:px-3 file:py-1.5 file:text-white hover:file:bg-brand-blue">
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Observações (opcional)" />
                    <textarea id="notes" name="notes" rows="3" maxlength="500" class="block mt-1 w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm">{{ old('notes', $appointment->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4 pt-2">
                    <a href="{{ $backUrl }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Salvar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
