<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit client: {{ $client->company_name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('clients.update', $client) }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('clients._form')
                <div class="flex gap-3">
                    <button class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Save changes</button>
                    <a href="{{ route('clients.show', $client) }}" class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>