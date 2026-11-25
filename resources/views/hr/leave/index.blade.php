<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave and Availability</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- pending --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Waiting for review ({{ $pending->count() }})</h3>
                <div class="mt-3 space-y-3">
                    @forelse ($pending as $r)
                        <div class="rounded-xl border border-gray-100 p-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $r->user->name }} <x-badge color="sky">{{ ucfirst($r->type) }}</x-badge></p>
                                    <p class="text-xs text-gray-500">{{ $r->start_date->format('d M') }} to {{ $r->end_date->format('d M Y') }} ({{ $r->days() }} days)</p>
                                    @if ($r->reason) <p class="mt-1 text-gray-600">{{ $r->reason }}</p> @endif
                                </div>
                                <div class="flex flex-wrap gap-2 items-start">
                                    <form method="POST" action="{{ route('hr.leave.approve', $r) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs hover:bg-emerald-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('hr.leave.reject', $r) }}" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="review_note" required placeholder="Reason" class="rounded-lg border-gray-300 text-xs w-44">
                                        <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700">Reject</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No pending requests.</p>
                    @endforelse
                </div>
            </div>

            {{-- calendar --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('hr.leave.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-sm hover:bg-gray-200">&larr; Prev</a>
                    <h3 class="font-semibold text-gray-800">Who is away: {{ $month->format('F Y') }}</h3>
                    <a href="{{ route('hr.leave.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-sm hover:bg-gray-200">Next &rarr;</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-[44rem]">
                        <div class="grid grid-cols-7 gap-1 text-xs font-medium text-gray-500 mb-1">
                            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)
                                <div class="px-1">{{ $d }}</div>
                            @endforeach
                        </div>
                        @foreach ($weeks as $week)
                            <div class="grid grid-cols-7 gap-1 mb-1">
                                @foreach ($week as $cell)
                                    <div class="min-h-[4.5rem] rounded-lg p-1.5 text-xs {{ $cell['inMonth'] ? 'bg-gray-50' : 'bg-white text-gray-300' }} {{ $cell['date']->isToday() ? 'ring-2 ring-indigo-400' : '' }}">
                                        <p class="font-medium">{{ $cell['date']->day }}</p>
                                        @foreach ($cell['away'] as $l)
                                            <p class="mt-0.5 truncate rounded bg-amber-100 text-amber-800 px-1" title="{{ $l->user->name }} ({{ $l->type }})">{{ $l->user->name }}</p>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- recent --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Recently decided</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($recent as $r)
                        <li class="py-2 flex items-center justify-between">
                            <span>{{ $r->user->name }}: {{ $r->start_date->format('d M') }} to {{ $r->end_date->format('d M Y') }}</span>
                            <span class="text-xs text-gray-500"><x-badge :color="$r->status === 'approved' ? 'emerald' : 'rose'">{{ ucfirst($r->status) }}</x-badge> by {{ $r->reviewer?->name }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-gray-400">Nothing yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
