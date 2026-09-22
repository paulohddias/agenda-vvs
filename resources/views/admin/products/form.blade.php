<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $product->exists ? 'Editar produto' : 'Novo produto' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @if ($product->exists) @method('PUT') @endif

                <div>
                    <x-input-label for="name" value="Nome" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $product->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Descrição" />
                    <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-brand-blue focus:ring-brand-blue rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="duration_minutes" value="Duração (minutos)" />
                        <x-text-input id="duration_minutes" name="duration_minutes" type="number" min="5" max="480" step="5" class="block mt-1 w-full" :value="old('duration_minutes', $product->duration_minutes)" required />
                        <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="price" value="Preço (opcional)" />
                        <x-text-input id="price" name="price" type="number" min="0" step="0.01" class="block mt-1 w-full" :value="old('price', $product->price)" />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>
                </div>

                <label class="inline-flex items-center">
                    <input type="checkbox" name="active" value="1" class="rounded border-gray-300 text-brand-blue shadow-sm focus:ring-brand-blue" @checked(old('active', $product->active))>
                    <span class="ms-2 text-sm text-gray-600">Disponível para agendamento</span>
                </label>

                <div class="flex items-center justify-end gap-4 pt-2">
                    <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Salvar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
