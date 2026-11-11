<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit user: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('admin.users._form')
                @if ($user->id === auth()->id())
                    <p class="text-sm text-amber-700 bg-amber-50 rounded-lg p-3">You cannot change your own role.</p>
                @endif
                <div class="flex gap-3">
                    <button class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Save changes</button>
                    <a href="{{ route('admin.users.index') }}" class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>