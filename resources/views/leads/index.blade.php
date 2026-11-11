<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lead Pipeline</h2>
            <a href="{{ route('inquiry.create') }}" target="_blank" class="text-sm text-indigo-600 hover:underline">Public inquiry form &nearr;</a>
        </div>
    </x-slot>

    @php
        $columnStyles = [
            'new' => 'border-sky-400 bg-sky-50',
            'contacted' => 'border-indigo-400 bg-indigo-50',
            'qualified' => 'border-violet-400 bg-violet-50',
            'proposal' => 'border-amber-400 bg-amber-50',
            'won' => 'border-emerald-400 bg-emerald-50',
            'lost' => 'border-rose-400 bg-rose-50',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, company or email"
                       class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Search</button>
                <a href="{{ route('leads.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="flex gap-4 overflow-x-auto pb-4">
                @foreach ($stages as $stage)
                    @php $items = $byStage->get($stage, collect()); @endphp
                    <div class="w-72 shrink-0 rounded-2xl border-t-4 p-3 shadow-sm {{ $columnStyles[$stage] }}">
                        <div class="flex items-center justify-between mb-3">
                            <x-stage-badge :stage="$stage" />
                            <span class="text-xs text-gray-500">{{ $items->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($items as $lead)
                                <div class="bg-white rounded-xl p-3 shadow-sm">
                                    <a href="{{ route('leads.show', $lead) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $lead->name }}</a>
                                    @if ($lead->company)
                                        <p class="text-xs text-gray-500">{{ $lead->company }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-600">{{ $lead->service ?: 'No service given' }}</p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $lead->created_at->diffForHumans() }}
                                        @if ($lead->assignee) · {{ $lead->assignee->name }} @endif
                                        @if ($lead->client_id) · <span class="text-emerald-600">Client</span> @endif
                                    </p>
                                    <form method="POST" action="{{ route('leads.stage', $lead) }}" class="mt-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="stage" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs">
                                            @foreach ($stages as $s)
                                                <option value="{{ $s }}" @selected($lead->stage === $s)>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 text-center py-4">No leads</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>