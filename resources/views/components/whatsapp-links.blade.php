@props(['appointment'])

{{-- Abrem o WhatsApp com a mensagem pronta; só aparecem para agendamentos ainda por acontecer. --}}
@if (in_array($appointment->status, ['pending', 'confirmed'], true) && $appointment->whatsappUrl('remind'))
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        <a href="{{ $appointment->whatsappUrl('confirm') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1.5 rounded-md bg-[#25D366] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#1ebe5a]">
            <x-whatsapp-icon /> Confirmar pelo WhatsApp
        </a>
        <a href="{{ $appointment->whatsappUrl('remind') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-1.5 rounded-md border border-[#25D366] px-3 py-1.5 text-sm font-medium text-green-700 hover:bg-green-50">
            <x-whatsapp-icon /> Lembrar pelo WhatsApp
        </a>
    </div>
@endif
