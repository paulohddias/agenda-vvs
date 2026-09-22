@php
    $linkFor = fn (array $overrides) => route('admin.appointments.index', array_merge([
        'view' => $view, 'status' => $status,
    ], $overrides));
@endphp

<div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2">
        <a href="{{ $linkFor(['day' => $prevDay->toDateString()]) }}"
           class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" aria-label="Anterior">&larr;</a>

        <div class="text-center w-64">
            <div class="font-medium text-gray-800">{{ $rangeLabel }}</div>
            @unless ($day->isToday())
                <a href="{{ $linkFor(['day' => today()->toDateString()]) }}" class="text-xs text-brand-blue hover:underline">Hoje</a>
            @endunless
        </div>

        <a href="{{ $linkFor(['day' => $nextDay->toDateString()]) }}"
           class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100" aria-label="Próximo">&rarr;</a>
    </div>

    <div class="flex items-center gap-2">
        <div class="inline-flex rounded-md border border-gray-200 overflow-hidden text-sm">
            @foreach (['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'] as $value => $label)
                <a href="{{ route('admin.appointments.index', ['view' => $value, 'status' => $status, 'day' => $day->toDateString()]) }}"
                   class="px-3 py-1.5 {{ $view === $value ? 'bg-brand-slate text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="hidden" name="day" value="{{ $day->toDateString() }}">
            <select name="status" onchange="this.form.submit()" class="border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm text-sm">
                <option value="">Todas as situações</option>
                @foreach (\App\Models\Appointment::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
