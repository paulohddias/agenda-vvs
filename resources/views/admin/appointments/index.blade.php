<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Agendamentos</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4"
             x-data="{
                open: false,
                appt: {},
                show(data) { this.appt = data; this.open = true; },
                close() { this.open = false; },
             }">
            <x-flash />

            @include('admin.appointments._toolbar')

            @if ($view === 'month')
                @include('admin.appointments._month')
            @else
                @include('admin.appointments._timeline')
            @endif

            @include('admin.appointments._modal')
        </div>
    </div>
</x-app-layout>
