<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $freelancer->name }}</h2>
            <a href="{{ route('freelancers.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to search</a>
        </div>
    </x-slot>

    @php $p = $freelancer->freelancerProfile; @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$freelancer" size="h-16 w-16" />
                    <div>
                        <p class="text-lg font-semibold text-gray-800">{{ $freelancer->name }}</p>
                        <p class="text-sm text-gray-500">{{ $p?->headline ?: 'Profile not completed' }}</p>
                    </div>
                </div>

                <div class="mt-6 grid sm:grid-cols-3 gap-4 text-center">
                    <div class="rounded-xl bg-indigo-50 p-4"><p class="text-xs text-indigo-600">Hourly rate</p><p class="text-xl font-bold text-indigo-700">{{ $p?->hourly_rate ? '$' . $p->hourly_rate : '—' }}</p></div>
                    <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-emerald-600">Availability</p><div class="mt-1">@if ($p) <x-status-badge :value="$p->availability" /> @else — @endif</div></div>
                    <div class="rounded-xl bg-sky-50 p-4"><p class="text-xs text-sky-600">Contact</p><p class="text-sm font-medium text-sky-700 break-all">{{ $freelancer->email }}</p></div>
                </div>

                <h3 class="mt-6 font-semibold text-gray-800">About</h3>
                <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $p?->bio ?: 'No description yet.' }}</p>

                <h3 class="mt-6 font-semibold text-gray-800">Skills</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($p?->skills ?? [] as $skill)
                        <x-badge color="violet">{{ $skill->name }}</x-badge>
                    @empty
                        <span class="text-sm text-gray-400">No skills added.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>