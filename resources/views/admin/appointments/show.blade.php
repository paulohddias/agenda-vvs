<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalhes do agendamento</h2>
            <x-status-badge :appointment="$appointment" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <x-whatsapp-links :appointment="$appointment" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-3">Atendimento</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Serviço</dt><dd class="font-medium text-gray-900">{{ $appointment->product->name }}</dd></div>
                    <div><dt class="text-gray-500">Data e hora</dt><dd class="font-medium text-gray-900">{{ $appointment->starts_at->format('d/m/Y H:i') }} – {{ $appointment->ends_at->format('H:i') }}</dd></div>
                    <div><dt class="text-gray-500">Forma de validação</dt><dd class="font-medium text-gray-900">{{ $appointment->validationMethodLabel() }}</dd></div>
                </dl>
                @if ($appointment->notes)
                    <div class="mt-4"><dt class="text-gray-500 text-sm">Observações</dt><dd class="text-gray-900">{{ $appointment->notes }}</dd></div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-3">Quem vai receber o certificado</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Nome ou razão social</dt><dd class="font-medium text-gray-900">{{ $appointment->holder_name }}</dd></div>
                    <div><dt class="text-gray-500">CPF/CNPJ</dt><dd class="font-medium text-gray-900">{{ $appointment->holderDocumentFormatted() }}</dd></div>
                    <div><dt class="text-gray-500">E-mail</dt><dd class="font-medium text-gray-900">{{ $appointment->holder_email }}</dd></div>
                    <div><dt class="text-gray-500">Telefone</dt><dd class="font-medium text-gray-900">{{ $appointment->holderPhoneFormatted() }}</dd></div>
                    @if ($appointment->accountant_name)
                        <div><dt class="text-gray-500">Contador</dt><dd class="font-medium text-gray-900">{{ $appointment->accountant_name }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500">Termos aceitos em</dt><dd class="font-medium text-gray-900">{{ $appointment->terms_accepted_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>

                <div class="mt-4">
                    <dt class="text-gray-500 text-sm">CNH / documento enviado</dt>
                    @if ($appointment->document_path)
                        <a href="{{ route('admin.appointments.document', $appointment) }}" class="inline-block mt-1 text-brand-blue hover:underline">Baixar documento</a>
                    @else
                        <dd class="text-gray-500">Nenhum documento enviado ainda.</dd>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.appointments.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Voltar à lista</a>
        </div>
    </div>
</x-app-layout>
