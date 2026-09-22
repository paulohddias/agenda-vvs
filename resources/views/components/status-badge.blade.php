@props(['appointment'])

@php
    $color = match ($appointment->status) {
        'confirmed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'completed' => 'bg-gray-100 text-gray-700',
        default => 'bg-yellow-100 text-yellow-800',
    };
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $appointment->statusLabel() }}</span>
