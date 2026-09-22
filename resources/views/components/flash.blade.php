@if (session('status'))
    <div id="flash-message" tabindex="-1" class="mb-4 rounded-md bg-green-50 border border-green-200 px-6 py-5 text-lg font-semibold text-green-800">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div id="flash-message" tabindex="-1" class="mb-4 rounded-md bg-red-50 border border-red-200 px-6 py-5 text-lg font-semibold text-red-800">{{ session('error') }}</div>
@endif

@if (session('status') || session('error'))
    <script>
        document.getElementById('flash-message')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    </script>
@endif
