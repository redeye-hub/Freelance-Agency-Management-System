<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New invoice</h2>
            <a href="{{ route('invoices.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; All invoices</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            {{-- step 1: pick the project --}}
            <form method="GET" action="{{ route('invoices.create') }}" class="bg-white rounded-2xl shadow-sm p-5 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[14rem]">
                    <x-input-label for="project" value="1. Project" />
                    <select id="project" name="project" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select a project</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($project?->id === $p->id)>{{ $p->name }} ({{ $p->client->company_name }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700">Show billable work</button>
            </form>

            @if ($project)
                {{-- step 2: choose what to bill --}}
                <form method="POST" action="{{ route('invoices.store') }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <x-input-error :messages="$errors->get('project_id')" />

                    <div>
                        <h3 class="font-semibold text-gray-800">2. Milestones not yet invoiced</h3>
                        <ul class="mt-2 divide-y divide-gray-100 text-sm">
                            @forelse ($milestones as $m)
                                <li class="py-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="milestone_ids[]" value="{{ $m->id }}" class="rounded border-gray-300" @checked(in_array($m->id, old('milestone_ids', [])))>
                                        <span class="flex-1">{{ $m->title }} <span class="text-xs text-gray-400">({{ ucfirst(str_replace('_', ' ', $m->status)) }})</span></span>
                                        <span class="font-medium">${{ number_format((float) $m->amount, 2) }}</span>
                                    </label>
                                </li>
                            @empty
                                <li class="py-2 text-gray-400">Nothing to bill here.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800">3. Approved hours not yet invoiced</h3>
                        <p class="text-xs text-gray-400">Only hours from approved timesheets appear. Each freelancer is billed at the hourly rate set on their assignment.</p>
                        <ul class="mt-2 divide-y divide-gray-100 text-sm">
                            @forelse ($hourGroups as $g)
                                <li class="py-2">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="hour_users[]" value="{{ $g['user']->id }}" class="rounded border-gray-300" @checked(in_array($g['user']->id, old('hour_users', [])))>
                                        <span class="flex-1">{{ $g['user']->name }} <span class="text-xs text-gray-400">{{ rtrim(rtrim(number_format($g['hours'], 2), '0'), '.') }} h × ${{ number_format($g['rate'], 2) }}</span></span>
                                        <span class="font-medium">${{ number_format($g['amount'], 2) }}</span>
                                    </label>
                                </li>
                            @empty
                                <li class="py-2 text-gray-400">No approved hours waiting to be billed.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <x-input-label for="due_date" value="Due date *" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', now()->addDays(14)->toDateString())" required />
                            <x-input-error class="mt-1" :messages="$errors->get('due_date')" />
                        </div>
                        <div>
                            <x-input-label for="tax_percent" value="Tax % *" />
                            <x-text-input id="tax_percent" name="tax_percent" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('tax_percent', 0)" required />
                            <x-input-error class="mt-1" :messages="$errors->get('tax_percent')" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label for="notes" value="Notes on the invoice" />
                            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Create draft invoice</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
