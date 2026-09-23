<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Agendamentos hoje</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $todayCount }}</div>
                </div>
                <a href="{{ route('admin.appointments.index', ['status' => 'pending']) }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Pendentes de confirmação</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $pendingCount }}</div>
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 font-medium text-gray-800">Próximos agendamentos</div>
                @forelse ($upcoming as $appointment)
                    <div class="px-6 py-3 flex items-center justify-between border-b border-gray-50 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $appointment->starts_at->format('d/m/Y H:i') }} · {{ $appointment->product->name }}</div>
                            <div class="text-sm text-gray-500">{{ $appointment->holder_name }} · {{ $appointment->holderPhoneFormatted() }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($url = $appointment->whatsappUrl('remind'))
                                <a href="{{ $url }}" target="_blank" rel="noopener" title="Lembrar pelo WhatsApp"
                                   class="inline-flex items-center gap-1 text-sm font-medium text-green-700 hover:underline">
                                    <x-whatsapp-icon /> Lembrar
                                </a>
                            @endif
                            <x-status-badge :appointment="$appointment" />
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500">Nenhum agendamento futuro.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
