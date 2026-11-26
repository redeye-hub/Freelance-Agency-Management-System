@php
    $me = auth()->user();
    $colors = ['applied' => 'bg-sky-500', 'screening' => 'bg-indigo-500', 'interview' => 'bg-amber-500', 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500'];
    $max = max(1, (int) ($stageCounts->max() ?? 0));
    $tiles = [
        ['New applicants', $stageCounts['applied'] ?? 0, 'text-sky-600', 'hr.candidates.index'],
        ['In interview', $stageCounts['interview'] ?? 0, 'text-amber-600', 'hr.candidates.index'],
        ['Leave to review', $pendingLeave, 'text-rose-600', 'hr.leave.index'],
        ['On leave today', $onLeaveToday, 'text-gray-800', 'hr.workload.index'],
        ['Contracts awaiting reply', $contractsAwaiting, 'text-indigo-600', 'hr.contracts.index'],
        ['Active freelancers', $activeFreelancers, 'text-emerald-600', 'hr.offboarding.index'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">HR Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white sm:rounded-lg p-8">
                <p class="text-sm text-gray-500">Signed in as HR</p>
                <h3 class="mt-1 text-3xl font-extrabold text-gray-800 tracking-tight">Welcome back, {{ $me->name }}</h3>
                <p class="mt-2 text-gray-600">Recruitment, leave, contracts and your team at a glance.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('hr.candidates.index') }}" class="bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open recruitment</a>
                    @if (Route::has('hr.leave.index'))
                        <a href="{{ route('hr.leave.index') }}" class="bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Review leave</a>
                    @endif
                    @if (Route::has('hr.reports.index'))
                        <a href="{{ route('hr.reports.index') }}" class="bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reports</a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                @foreach ($tiles as [$label, $value, $color, $routeName])
                    @if (Route::has($routeName))
                        <a href="{{ route($routeName) }}" class="bg-white p-5 sm:rounded-lg hover:shadow">
                            <p class="text-sm text-gray-500">{{ $label }}</p>
                            <p class="mt-1 text-3xl font-bold {{ $color }}">{{ $value }}</p>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Recruitment pipeline</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        @foreach ($stages as $stage)
                            @php $n = $stageCounts[$stage] ?? 0; @endphp
                            <div>
                                <div class="flex justify-between"><span class="capitalize">{{ $stage }}</span><span class="text-gray-500">{{ $n }}</span></div>
                                <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full {{ $colors[$stage] }}" style="width: {{ round($n / $max * 100) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Upcoming interviews</h3>
                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @forelse ($interviews as $c)
                            <li class="py-2 flex justify-between gap-2">
                                <a href="{{ route('hr.candidates.show', $c) }}" class="text-indigo-600 hover:underline">{{ $c->name }}</a>
                                <span class="text-gray-500">{{ $c->interview_at->format('d M, H:i') }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-gray-400">No interviews scheduled.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Leave waiting for you</h3>
                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @forelse ($leaveQueue as $l)
                            <li class="py-2 flex justify-between gap-2">
                                <span>{{ $l->user->name }} <span class="text-gray-400">{{ ucfirst($l->type) }}</span></span>
                                <span class="text-gray-500">{{ $l->start_date->format('d M') }} to {{ $l->end_date->format('d M') }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-gray-400">Nothing pending.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">Recent HR activity</h3>
                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @forelse ($recentActivity as $a)
                            <li class="py-2 flex justify-between gap-2">
                                <span>{{ $a->user?->name ?? 'System' }} <span class="text-gray-400">{{ $a->action }}</span></span>
                                <span class="text-gray-500">{{ $a->created_at->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-gray-400">No activity yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
