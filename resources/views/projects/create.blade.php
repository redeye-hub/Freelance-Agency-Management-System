<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New project</h2>
            <a href="{{ route('projects.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to projects</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('projects.store') }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                @include('projects._form')
                <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Create project</button>
            </form>
        </div>
    </div>
</x-app-layout>
