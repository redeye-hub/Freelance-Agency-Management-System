<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invite user</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                @csrf
                @include('admin.users._form')
                <p class="text-sm text-sky-700 bg-sky-50 rounded-lg p-3">An email with a "set your password" link is sent to this address. In development it is written to <code>storage/logs/laravel.log</code>.</p>
                <div class="flex gap-3">
                    <button class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Send invitation</button>
                    <a href="{{ route('admin.users.index') }}" class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>