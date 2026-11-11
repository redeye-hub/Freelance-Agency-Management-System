<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Find a Freelancer</h2>
    </x-slot>

    @php
        $chips = [
            'bg-indigo-50 text-indigo-700',
            'bg-emerald-50 text-emerald-700',
            'bg-amber-50 text-amber-700',
            'bg-sky-50 text-sky-700',
            'bg-violet-50 text-violet-700',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white p-4 rounded-2xl shadow-sm grid sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <div class="lg:col-span-1">
                    <label class="text-xs text-gray-500">Name</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-xs text-gray-500">Skills (comma separated)</label>
                    <input type="text" name="skill" value="{{ request('skill') }}" placeholder="laravel, vue" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Min $/hr</label>
                    <input type="number" step="0.01" min="0" name="min_rate" value="{{ request('min_rate') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Max $/hr</label>
                    <input type="number" step="0.01" min="0" name="max_rate" value="{{ request('max_rate') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Availability</label>
                    <select name="availability" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Any</option>
                        @foreach (\App\Models\FreelancerProfile::AVAILABILITY as $a)
                            <option value="{{ $a }}" @selected(request('availability') === $a)>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-6 flex gap-3">
                    <button class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Search</button>
                    <a href="{{ route('freelancers.index') }}" class="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($freelancers as $f)
                    @php $p = $f->freelancerProfile; @endphp
                    <a href="{{ route('freelancers.show', $f) }}" class="block bg-white rounded-2xl shadow-sm hover:shadow-md transition p-5">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$f" size="h-12 w-12" />
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate">{{ $f->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $p?->headline ?: 'Profile not completed' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="font-semibold text-indigo-600">{{ $p?->hourly_rate ? '$' . $p->hourly_rate . '/hr' : 'No rate' }}</span>
                            @if ($p) <x-status-badge :value="$p->availability" /> @endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($p?->skills ?? [] as $skill)
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $chips[$loop->index % 5] }}">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </a>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-2xl p-10 text-center text-gray-400">No freelancers match your search.</div>
                @endforelse
            </div>

            {{ $freelancers->links() }}
        </div>
    </div>
</x-app-layout>