<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <header class="bg-brand-slate border-b-4 border-brand-green">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}"><x-application-logo class="block h-12 w-auto" /></a>

                {{-- O agendamento é público; login existe só para o painel administrativo. --}}
                @auth
                    <nav class="flex items-center gap-4 text-sm font-medium text-gray-200">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-white">Painel</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="hover:text-white">Sair</button>
                        </form>
                    </nav>
                @endauth
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <x-flash />
            {{ $slot }}
        </main>

        @include('partials.location-map')

        @stack('scripts')
    </body>
</html>
