<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Freelancer Workload</h2>
    </x-slot>

    @php
        $badge = ['over' => ['rose', 'Over-allocated'], 'high' => ['amber', 'Near limit'], 'ok' => ['emerald', 'Healthy'], 'free' => ['sky', 'Free']];
        $bar = ['over' => 'bg-rose-500', 'high' => 'bg-amber-500', 'ok' => 'bg-emerald-500', 'free' => 'bg-sky-400'];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Active freelancers</p><p class="text-3xl font-bold text-gray-800">{{ $rows->count() }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Over-allocated</p><p class="text-3xl font-bold text-rose-600">{{ $overCount }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">With no work planned</p><p class="text-3xl font-bold text-sky-600">{{ $freeCount }}</p></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">Freelancer</th>
                            <th class="px-4 py-3">Projects</th>
                            <th class="px-4 py-3 w-64">Planned ({{ $capacity }} h/week capacity)</th>
                            <th class="px-4 py-3">Logged this week</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($rows as $r)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800">{{ $r['user']->name }}</p>
                                    @if ($r['on_leave_until'])
                                        <p class="text-xs text-amber-600">On leave until {{ $r['on_leave_until']->format('d M') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $r['projects']->isEmpty() ? 'None' : $r['projects']->implode(', ') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-between text-xs text-gray-500"><span>{{ $r['planned'] }} h</span><span>{{ $r['percent'] }}%</span></div>
                                    <div class="mt-1 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full {{ $bar[$r['level']] }}" style="width: {{ min(100, $r['percent']) }}%"></div></div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($r['logged'], 2) }} h</td>
                                <td class="px-4 py-3"><x-badge :color="$badge[$r['level']][0]">{{ $badge[$r['level']][1] }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No active freelancers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400">Planned hours come from active assignments on planning and active projects (set by the project manager). Logged hours include every timesheet status for the current week.</p>
        </div>
    </div>
</x-app-layout>
