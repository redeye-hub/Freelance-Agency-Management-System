<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->name }}</h2>
            <div class="flex gap-4 text-sm">
                @if (Route::has('projects.board'))
                    <a href="{{ route('projects.board', $project) }}" class="text-sky-600 hover:underline">Task board</a>
                @endif
                @if ($project->status === 'completed' && Route::has('projects.reviews.edit'))
                    <a href="{{ route('projects.reviews.edit', $project) }}" class="text-emerald-600 hover:underline">Rate freelancers</a>
                @endif
                <a href="{{ route('projects.edit', $project) }}" class="text-indigo-600 hover:underline">Edit</a>
                <a href="{{ route('projects.index') }}" class="text-gray-500 hover:underline">&larr; All projects</a>
            </div>
        </div>
    </x-slot>

    @php
        $mColors = ['pending' => 'gray', 'in_progress' => 'amber', 'completed' => 'emerald'];
        $progress = $project->milestoneProgress();
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- summary --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <dl class="grid sm:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-gray-500">Client</dt><dd class="font-medium text-gray-800">{{ $project->client->company_name }}</dd></div>
                    <div><dt class="text-gray-500">Manager</dt><dd class="font-medium text-gray-800">{{ $project->manager?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-gray-500">Budget</dt><dd class="font-medium text-gray-800">${{ number_format((float) $project->budget, 2) }}</dd></div>
                    <div><dt class="text-gray-500">Deadline</dt><dd class="font-medium text-gray-800">{{ $project->deadline?->format('d M Y') ?? 'N/A' }}</dd></div>
                </dl>
                @if ($project->description)
                    <p class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $project->description }}</p>
                @endif
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500"><span>Milestone progress</span><span>{{ $progress }}%</span></div>
                    <div class="mt-1 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div></div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- milestones --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Milestones</h3>
                        <span class="text-xs text-gray-500">${{ number_format($milestoneTotal, 2) }} of ${{ number_format((float) $project->budget, 2) }} allocated</span>
                    </div>

                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @forelse ($project->milestones as $m)
                            <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $m->title }}</p>
                                    <p class="text-xs text-gray-500">${{ number_format((float) $m->amount, 2) }}@if ($m->due_date) · due {{ $m->due_date->format('d M Y') }} @endif</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('projects.milestones.update', [$project, $m]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-xs">
                                            @foreach (\App\Models\Milestone::STATUSES as $s)
                                                <option value="{{ $s }}" @selected($m->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('projects.milestones.destroy', [$project, $m]) }}" onsubmit="return confirm('Remove this milestone?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-rose-600 hover:underline">Remove</button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="py-3 text-gray-400">No milestones yet.</li>
                        @endforelse
                    </ul>

                    <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="mt-4 grid grid-cols-6 gap-2">
                        @csrf
                        <input type="text" name="title" placeholder="Title" required value="{{ old('title') }}" class="col-span-6 sm:col-span-3 rounded-lg border-gray-300 text-sm">
                        <input type="number" name="amount" step="0.01" min="0" placeholder="Amount" required value="{{ old('amount') }}" class="col-span-3 sm:col-span-1 rounded-lg border-gray-300 text-sm">
                        <input type="date" name="due_date" value="{{ old('due_date') }}" class="col-span-3 sm:col-span-2 rounded-lg border-gray-300 text-sm">
                        <button class="col-span-6 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Add milestone</button>
                    </form>
                    <x-input-error class="mt-1" :messages="$errors->get('amount')" />
                </div>

                {{-- team --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Team and rates</h3>

                    <div class="mt-3 space-y-3 text-sm">
                        @forelse ($project->assignments as $a)
                            <form method="POST" action="{{ route('projects.assignments.update', [$project, $a]) }}"
                                  class="rounded-xl border border-gray-100 p-3 grid grid-cols-2 sm:grid-cols-5 gap-2 items-end {{ $a->status === 'ended' ? 'opacity-60' : '' }}">
                                @csrf
                                @method('PATCH')
                                <div class="col-span-2 sm:col-span-5 font-medium text-gray-800">
                                    {{ $a->user->name }}
                                    @if ($a->status === 'ended') <x-badge color="gray">Ended</x-badge> @endif
                                    @if (class_exists(\App\Models\Contract::class) && $a->contract) <x-badge color="teal">Contract: {{ $a->contract->status }}</x-badge> @endif
                                </div>
                                <label class="text-xs text-gray-500">Rate/hr
                                    <input type="number" step="0.01" min="0" name="hourly_rate" value="{{ $a->hourly_rate }}" class="mt-0.5 w-full rounded-lg border-gray-300 text-sm">
                                </label>
                                <label class="text-xs text-gray-500">Commission %
                                    <input type="number" step="0.01" min="0" max="100" name="commission_percent" value="{{ $a->commission_percent }}" class="mt-0.5 w-full rounded-lg border-gray-300 text-sm">
                                </label>
                                <label class="text-xs text-gray-500">Hours/week
                                    <input type="number" min="1" max="80" name="weekly_hours" value="{{ $a->weekly_hours }}" class="mt-0.5 w-full rounded-lg border-gray-300 text-sm">
                                </label>
                                <label class="text-xs text-gray-500">Status
                                    <select name="status" class="mt-0.5 w-full rounded-lg border-gray-300 text-sm">
                                        <option value="active" @selected($a->status === 'active')>Active</option>
                                        <option value="ended" @selected($a->status === 'ended')>Ended</option>
                                    </select>
                                </label>
                                <button class="px-3 py-2 rounded-lg bg-sky-600 text-white text-xs hover:bg-sky-700">Save</button>
                            </form>
                        @empty
                            <p class="text-gray-400">Nobody assigned yet.</p>
                        @endforelse
                    </div>

                    <h4 class="mt-5 text-sm font-semibold text-gray-700">Assign a freelancer</h4>
                    <form method="POST" action="{{ route('projects.assignments.store', $project) }}" class="mt-2 grid grid-cols-2 sm:grid-cols-5 gap-2">
                        @csrf
                        <select name="user_id" required class="col-span-2 sm:col-span-5 rounded-lg border-gray-300 text-sm">
                            <option value="">Select a freelancer</option>
                            @foreach ($freelancers as $f)
                                <option value="{{ $f->id }}" @selected((int) old('user_id') === $f->id)>
                                    {{ $f->name }} ({{ $f->freelancerProfile?->availability ?? 'no profile' }}, ${{ $f->freelancerProfile?->hourly_rate ?? '0' }}/hr)
                                </option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" name="hourly_rate" placeholder="Rate/hr" required value="{{ old('hourly_rate') }}" class="rounded-lg border-gray-300 text-sm">
                        <input type="number" step="0.01" min="0" max="100" name="commission_percent" placeholder="Commission %" required value="{{ old('commission_percent', 10) }}" class="rounded-lg border-gray-300 text-sm">
                        <input type="number" min="1" max="80" name="weekly_hours" placeholder="Hours/week" required value="{{ old('weekly_hours', 20) }}" class="rounded-lg border-gray-300 text-sm">
                        <button class="col-span-2 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Assign</button>
                    </form>
                    <x-input-error class="mt-1" :messages="$errors->get('user_id')" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
