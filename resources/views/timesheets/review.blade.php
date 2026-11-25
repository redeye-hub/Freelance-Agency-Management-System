<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Timesheet Approvals</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <h3 class="font-semibold text-gray-800">Waiting for review ({{ $pending->count() }})</h3>

            @forelse ($pending as $t)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $t->user->name }}</p>
                            <p class="text-xs text-gray-500">Week of {{ $t->week_start->format('d M Y') }} · submitted {{ $t->submitted_at?->diffForHumans() }}</p>
                        </div>
                        <p class="text-lg font-bold text-indigo-600">{{ number_format((float) $t->entries->sum('hours'), 2) }} h</p>
                    </div>

                    <table class="mt-3 min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($t->entries->sortBy('work_date') as $e)
                                <tr>
                                    <td class="py-1.5 pr-3 text-gray-500">{{ $e->work_date->format('D d M') }}</td>
                                    <td class="py-1.5 pr-3">{{ $e->project->name }}</td>
                                    <td class="py-1.5 pr-3 text-gray-500">{{ $e->description }}</td>
                                    <td class="py-1.5 text-right font-medium">{{ number_format((float) $e->hours, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4 flex flex-wrap gap-2 items-start">
                        <form method="POST" action="{{ route('timesheets.approve', $t) }}">
                            @csrf
                            @method('PATCH')
                            <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('timesheets.reject', $t) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="review_note" required placeholder="Reason for sending back" class="rounded-lg border-gray-300 text-sm w-64">
                            <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Send back</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="bg-white rounded-2xl shadow-sm p-6 text-sm text-gray-400">Nothing waiting. All caught up.</p>
            @endforelse

            <h3 class="font-semibold text-gray-800">Recently reviewed</h3>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($recent as $t)
                        <li class="py-2 flex items-center justify-between">
                            <span>{{ $t->user->name }}, week of {{ $t->week_start->format('d M Y') }}</span>
                            <span class="text-xs text-gray-500">
                                <x-badge :color="$t->status === 'approved' ? 'emerald' : 'rose'">{{ ucfirst($t->status) }}</x-badge>
                                by {{ $t->reviewer?->name }}
                            </span>
                        </li>
                    @empty
                        <li class="py-2 text-gray-400">Nothing reviewed yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
