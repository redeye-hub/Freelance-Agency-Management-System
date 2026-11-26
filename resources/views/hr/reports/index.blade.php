<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">HR Reports</h2>
    </x-slot>

    @php
        $query = ['from' => $r['from']->toDateString(), 'to' => $r['to']->toDateString()];
        $f = $r['funnel'];
        $maxStage = max(1, max($f['stages']));
        $stageColors = ['applied' => 'bg-sky-500', 'screening' => 'bg-indigo-500', 'interview' => 'bg-amber-500', 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500'];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap items-end gap-3">
                <label class="text-xs text-gray-500">From
                    <input type="date" name="from" value="{{ $query['from'] }}" class="mt-0.5 block rounded-lg border-gray-300 text-sm">
                </label>
                <label class="text-xs text-gray-500">To
                    <input type="date" name="to" value="{{ $query['to'] }}" class="mt-0.5 block rounded-lg border-gray-300 text-sm">
                </label>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Apply</button>
                <div class="ml-auto flex gap-2 text-sm">
                    <a href="{{ route('hr.reports.export', $query + ['format' => 'csv']) }}" class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Export CSV</a>
                    <a href="{{ route('hr.reports.export', $query + ['format' => 'pdf']) }}" class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100">Export PDF</a>
                </div>
            </form>

            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Applications</p><p class="text-3xl font-bold text-gray-800">{{ $f['total'] }}</p><p class="text-xs text-gray-400">{{ $f['approval_rate'] }}% approved</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Leave days taken</p><p class="text-3xl font-bold text-amber-600">{{ $r['leave']['total_days'] }}</p><p class="text-xs text-gray-400">{{ $r['leave']['pending_now'] }} requests pending</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Average utilization</p><p class="text-3xl font-bold text-indigo-600">{{ $r['utilization']['average'] }}%</p><p class="text-xs text-gray-400">{{ $r['utilization']['over'] }} over, {{ $r['utilization']['idle'] }} idle</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Average rating</p><p class="text-3xl font-bold text-emerald-600">{{ $r['ratings']['overall'] !== null ? $r['ratings']['overall'] . ' / 5' : 'N/A' }}</p><p class="text-xs text-gray-400">{{ $r['ratings']['count'] }} reviews</p></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Onboarding funnel</h3>
                    <p class="text-xs text-gray-400">People who applied in this period, by where they are now.</p>
                    <div class="mt-3 space-y-2 text-sm">
                        @foreach ($f['stages'] as $stage => $n)
                            <div>
                                <div class="flex justify-between"><span>{{ ucfirst($stage) }}</span><span class="text-gray-500">{{ $n }}</span></div>
                                <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full {{ $stageColors[$stage] }}" style="width: {{ round($n / $maxStage * 100) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Leave in this period</h3>
                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @forelse ($r['leave']['by_type'] as $type => $days)
                            <li class="py-1.5 flex justify-between"><span>{{ ucfirst($type) }}</span><span class="font-medium">{{ $days }} days</span></li>
                        @empty
                            <li class="py-1.5 text-gray-400">No approved leave.</li>
                        @endforelse
                    </ul>
                    @if (count($r['leave']['by_person']))
                        <h4 class="mt-4 text-sm font-medium text-gray-700">Most days away</h4>
                        <ul class="mt-1 text-sm text-gray-600">
                            @foreach (array_slice($r['leave']['by_person'], 0, 5, true) as $name => $days)
                                <li class="flex justify-between"><span>{{ $name }}</span><span>{{ $days }} d</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <div class="px-6 pt-5"><h3 class="font-semibold text-gray-800">Utilization (capacity {{ $r['utilization']['capacity'] }} h/week)</h3></div>
                <table class="mt-3 min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left"><tr><th class="px-4 py-3">Freelancer</th><th class="px-4 py-3">Planned h/week</th><th class="px-4 py-3 w-56">Planned %</th><th class="px-4 py-3">Hours logged</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($r['utilization']['rows'] as $row)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                                <td class="px-4 py-2">{{ $row['planned'] }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex justify-between text-xs text-gray-500"><span>{{ $row['percent'] }}%</span></div>
                                    <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full {{ $row['percent'] > 100 ? 'bg-rose-500' : ($row['percent'] >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $row['percent']) }}%"></div></div>
                                </td>
                                <td class="px-4 py-2">{{ number_format($row['logged'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">No active freelancers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <div class="px-6 pt-5"><h3 class="font-semibold text-gray-800">Ratings in this period</h3></div>
                <table class="mt-3 min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left"><tr><th class="px-4 py-3">Freelancer</th><th class="px-4 py-3">Reviews</th><th class="px-4 py-3">Quality</th><th class="px-4 py-3">Communication</th><th class="px-4 py-3">Timeliness</th><th class="px-4 py-3">Overall</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($r['ratings']['people'] as $p)
                            <tr><td class="px-4 py-2 font-medium">{{ $p['name'] }}</td><td class="px-4 py-2">{{ $p['reviews'] }}</td><td class="px-4 py-2">{{ $p['quality'] }}</td><td class="px-4 py-2">{{ $p['communication'] }}</td><td class="px-4 py-2">{{ $p['timeliness'] }}</td><td class="px-4 py-2 font-semibold">{{ $p['overall'] }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">No reviews saved in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
