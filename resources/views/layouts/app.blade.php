<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @if (auth()->user()?->isAdmin())
            {{-- Avisos de cliente prestes a chegar (resources/js/app.js → VVSAlerts). Ficam até alguém fechar. --}}
            <div id="vvs-alerts" data-url="{{ route('admin.alerts.upcoming') }}"
                 class="fixed top-4 right-4 z-[60] w-80 max-w-[calc(100%-2rem)] space-y-3" aria-live="assertive"></div>

            <template id="vvs-alert-template">
                <div class="rounded-lg border-l-4 border-brand-green bg-white p-4 shadow-lg ring-1 ring-black/5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm">
                            <div class="font-semibold text-brand-slate" data-field="title"></div>
                            <div class="mt-1 font-medium text-gray-900" data-field="holderName"></div>
                            <div class="text-gray-600" data-field="details"></div>
                            <a data-field="url" class="mt-2 inline-block font-medium text-brand-blue hover:underline">Ver na agenda</a>
                        </div>
                        <button type="button" data-action="close" class="text-xl leading-none text-gray-400 hover:text-gray-600" aria-label="Fechar aviso">&times;</button>
                    </div>
                </div>
            </template>
        @endif
    </body>
</html>
