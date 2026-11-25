<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit: {{ $project->name }}</h2>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to project</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('projects.update', $project) }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                @method('PUT')
                @include('projects._form')
                <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Save changes</button>
            </form>
        </div>
    </div>
</x-app-layout>
