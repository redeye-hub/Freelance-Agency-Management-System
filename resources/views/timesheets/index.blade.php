<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Timesheet</h2>
    </x-slot>

    @php
        $colors = ['draft' => 'gray', 'submitted' => 'amber', 'approved' => 'emerald', 'rejected' => 'rose'];
        $editable = $timesheet->isEditable();
        $byDay = $timesheet->entries->groupBy(fn ($e) => $e->work_date->toDateString());
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('timesheet.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-sm hover:bg-gray-200">&larr; Prev</a>
                        <div>
                            <p class="font-semibold text-gray-800">Week of {{ $weekStart->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $weekStart->format('d M') }} to {{ $weekStart->copy()->addDays(6)->format('d M Y') }}</p>
                        </div>
                        <a href="{{ route('timesheet.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="px-3 py-1.5 rounded-lg bg-gray-100 text-sm hover:bg-gray-200">Next &rarr;</a>
                    </div>
                    <div class="text-right">
                        <x-badge :color="$colors[$timesheet->status]">{{ ucfirst($timesheet->status) }}</x-badge>
                        <p class="mt-1 text-sm text-gray-700"><strong>{{ number_format($timesheet->entries->sum('hours'), 2) }}</strong> hours this week</p>
                    </div>
                </div>

                @if ($timesheet->status === 'rejected' && $timesheet->review_note)
                    <p class="mt-4 text-sm p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700">
                        Sent back by {{ $timesheet->reviewer?->name }}: {{ $timesheet->review_note }}
                    </p>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr><th class="px-4 py-3">Day</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Notes</th><th class="px-4 py-3">Hours</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($days as $day)
                            @php $entries = $byDay->get($day->toDateString(), collect()); @endphp
                            @forelse ($entries as $e)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $loop->first ? $day->format('D d M') : '' }}</td>
                                    <td class="px-4 py-2">{{ $e->project->name }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $e->description ?: 'N/A' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ number_format((float) $e->hours, 2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        @if ($editable)
                                            <form method="POST" action="{{ route('timesheet.entries.destroy', $e) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-xs text-rose-600 hover:underline">Remove</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="text-gray-300"><td class="px-4 py-2">{{ $day->format('D d M') }}</td><td colspan="4" class="px-4 py-2">No time logged</td></tr>
                            @endforelse
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($editable)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Log hours</h3>
                    @if ($projects->isEmpty())
                        <p class="text-sm text-gray-500">You are not assigned to any active project yet.</p>
                    @else
                        <form method="POST" action="{{ route('timesheet.entries.store') }}" class="grid sm:grid-cols-5 gap-2">
                            @csrf
                            <select name="project_id" required class="rounded-lg border-gray-300 text-sm">
                                @foreach ($projects as $p)
                                    <option value="{{ $p->id }}" @selected((int) old('project_id') === $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="work_date" required max="{{ now()->toDateString() }}" value="{{ old('work_date', min(now(), $weekStart->copy()->addDays(6))->toDateString()) }}" class="rounded-lg border-gray-300 text-sm">
                            <input type="number" name="hours" step="0.25" min="0.25" max="24" required placeholder="Hours" value="{{ old('hours') }}" class="rounded-lg border-gray-300 text-sm">
                            <input type="text" name="description" maxlength="255" placeholder="What did you do?" value="{{ old('description') }}" class="rounded-lg border-gray-300 text-sm">
                            <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Add</button>
                        </form>
                        <x-input-error class="mt-1" :messages="$errors->get('hours')" />
                        <x-input-error class="mt-1" :messages="$errors->get('work_date')" />
                        <x-input-error class="mt-1" :messages="$errors->get('project_id')" />
                    @endif

                    <form method="POST" action="{{ route('timesheet.submit') }}" class="mt-5" onsubmit="return confirm('Submit this week for approval? You cannot edit it afterwards.')">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Submit week for approval</button>
                    </form>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Recent weeks</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($history as $h)
                        <li class="py-2 flex items-center justify-between">
                            <a href="{{ route('timesheet.index', ['week' => $h->week_start->toDateString()]) }}" class="text-indigo-600 hover:underline">Week of {{ $h->week_start->format('d M Y') }}</a>
                            <x-badge :color="$colors[$h->status]">{{ ucfirst($h->status) }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
