<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Produtos</h2>
            <a href="{{ route('admin.products.create') }}"><x-primary-button type="button">Novo produto</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nome</th>
                            <th class="px-6 py-3 font-medium">Duração</th>
                            <th class="px-6 py-3 font-medium">Preço</th>
                            <th class="px-6 py-3 font-medium">Situação</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-6 py-3 text-gray-900">{{ $product->name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $product->duration_minutes }} min</td>
                                <td class="px-6 py-3 text-gray-600">{{ $product->price !== null ? 'R$ '.number_format($product->price, 2, ',', '.') : '—' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $product->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $product->active ? 'Ativo' : 'Inativo' }}</span>
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-brand-blue hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Excluir este produto?')">
                                        @csrf @method('DELETE')
                                        <button class="ms-3 text-red-600 hover:underline">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum produto cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
