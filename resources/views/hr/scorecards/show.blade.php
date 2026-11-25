<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scorecard: {{ $freelancer->name }}</h2>
            <a href="{{ route('hr.scorecards.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; All scorecards</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500">Overall average</p>
                <p class="text-4xl font-bold text-indigo-600">{{ $overall !== null ? $overall . ' / 5' : 'Not rated yet' }}</p>
            </div>

            @forelse ($reviews as $r)
                <div class="bg-white rounded-2xl shadow-sm p-5 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-medium text-gray-800">{{ $r->project->name }}</p>
                        <x-badge color="indigo">{{ $r->overall() }} / 5</x-badge>
                    </div>
                    <p class="mt-1 text-gray-600">Quality {{ $r->quality }} · Communication {{ $r->communication }} · Timeliness {{ $r->timeliness }}</p>
                    @if ($r->comment) <p class="mt-2 text-gray-700 bg-gray-50 rounded-xl p-3">{{ $r->comment }}</p> @endif
                    <p class="mt-2 text-xs text-gray-400">Rated by {{ $r->reviewer?->name ?? 'N/A' }} on {{ $r->updated_at->format('d M Y') }}</p>
                </div>
            @empty
                <p class="bg-white rounded-2xl shadow-sm p-6 text-sm text-gray-400">No reviews yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
