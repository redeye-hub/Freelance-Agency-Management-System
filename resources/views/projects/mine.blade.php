<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Projects</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($projects as $p)
                    <a href="{{ route('projects.board', $p) }}" class="block bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">
                        <p class="font-semibold text-gray-800">{{ $p->name }}</p>
                        <p class="text-sm text-gray-500">{{ $p->client->company_name }}</p>
                        <p class="mt-2 text-xs text-gray-400">
                            Deadline: {{ $p->deadline?->format('d M Y') ?? 'N/A' }} · {{ ucfirst(str_replace('_', ' ', $p->status)) }}
                        </p>
                        <p class="mt-3 text-sm text-indigo-600">Open task board &rarr;</p>
                    </a>
                @empty
                    <p class="sm:col-span-2 bg-white rounded-2xl shadow-sm p-6 text-sm text-gray-400">You are not assigned to any project yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
