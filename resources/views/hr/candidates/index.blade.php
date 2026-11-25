<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recruitment Pipeline</h2>
            <a href="{{ route('apply.create') }}" target="_blank" class="text-sm text-indigo-600 hover:underline">Public application form &nearr;</a>
        </div>
    </x-slot>

    @php
        $columnStyles = [
            'applied' => 'border-sky-400 bg-sky-50',
            'screening' => 'border-indigo-400 bg-indigo-50',
            'interview' => 'border-amber-400 bg-amber-50',
            'approved' => 'border-emerald-400 bg-emerald-50',
            'rejected' => 'border-rose-400 bg-rose-50',
        ];
        $badgeColors = [
            'applied' => 'sky', 'screening' => 'indigo', 'interview' => 'amber',
            'approved' => 'emerald', 'rejected' => 'rose',
        ];
        $movable = array_values(array_diff($stages, ['approved']));
    @endphp

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email or skill"
                       class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Search</button>
                <a href="{{ route('hr.candidates.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="flex gap-4 overflow-x-auto pb-4">
                @foreach ($stages as $stage)
                    @php $items = $byStage->get($stage, collect()); @endphp
                    <div class="w-72 shrink-0 rounded-2xl border-t-4 p-3 shadow-sm {{ $columnStyles[$stage] }}">
                        <div class="flex items-center justify-between mb-3">
                            <x-badge :color="$badgeColors[$stage]">{{ ucfirst($stage) }}</x-badge>
                            <span class="text-xs text-gray-500">{{ $items->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($items as $c)
                                <div class="bg-white rounded-xl p-3 shadow-sm">
                                    <a href="{{ route('hr.candidates.show', $c) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $c->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $c->headline ?: 'No headline' }}</p>
                                    @if ($c->skills)
                                        <p class="mt-1 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($c->skills, 40) }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $c->created_at->diffForHumans() }}
                                        @if ($c->interview_at) · Interview {{ $c->interview_at->format('d M, H:i') }} @endif
                                    </p>

                                    @if ($c->stage !== 'approved')
                                        <form method="POST" action="{{ route('hr.candidates.stage', $c) }}" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="stage" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs">
                                                @foreach ($movable as $s)
                                                    <option value="{{ $s }}" @selected($c->stage === $s)>{{ ucfirst($s) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 text-center py-4">No candidates</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
