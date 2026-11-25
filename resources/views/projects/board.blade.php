<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->name }}: Task board</h2>
            @if ($canManage)
                <a href="{{ route('projects.show', $project) }}" class="text-sm text-indigo-600 hover:underline">&larr; Project details</a>
            @else
                <a href="{{ route('my.projects') }}" class="text-sm text-indigo-600 hover:underline">&larr; My projects</a>
            @endif
        </div>
    </x-slot>

    @php
        $columnStyles = [
            'todo' => 'border-gray-400 bg-gray-50',
            'in_progress' => 'border-sky-400 bg-sky-50',
            'review' => 'border-amber-400 bg-amber-50',
            'done' => 'border-emerald-400 bg-emerald-50',
        ];
        $pColors = ['low' => 'gray', 'medium' => 'amber', 'high' => 'rose'];
        $me = auth()->id();
    @endphp

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2 text-sm">
                    <a href="{{ route('projects.board', $project) }}" class="px-3 py-1.5 rounded-lg {{ request()->boolean('mine') ? 'bg-gray-100 text-gray-700' : 'bg-indigo-600 text-white' }}">All tasks</a>
                    <a href="{{ route('projects.board', [$project, 'mine' => 1]) }}" class="px-3 py-1.5 rounded-lg {{ request()->boolean('mine') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">My tasks</a>
                </div>

                @if ($canManage)
                    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
                        <button @click="open = ! open" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New task</button>

                        <form x-show="open" x-cloak method="POST" action="{{ route('projects.tasks.store', $project) }}"
                              class="mt-3 bg-white rounded-2xl shadow-sm p-4 grid sm:grid-cols-6 gap-2 w-full sm:w-[48rem]">
                            @csrf
                            <input type="text" name="title" required placeholder="Task title" value="{{ old('title') }}" class="sm:col-span-6 rounded-lg border-gray-300 text-sm">
                            <textarea name="description" rows="2" placeholder="Description (optional)" class="sm:col-span-6 rounded-lg border-gray-300 text-sm">{{ old('description') }}</textarea>
                            <select name="assignee_id" class="sm:col-span-2 rounded-lg border-gray-300 text-sm">
                                <option value="">Unassigned</option>
                                @foreach ($team as $t)
                                    <option value="{{ $t->id }}" @selected((int) old('assignee_id') === $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <select name="priority" class="sm:col-span-1 rounded-lg border-gray-300 text-sm">
                                @foreach ($priorities as $p)
                                    <option value="{{ $p }}" @selected(old('priority', 'medium') === $p)>{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="due_date" value="{{ old('due_date') }}" class="sm:col-span-2 rounded-lg border-gray-300 text-sm">
                            <select name="milestone_id" class="sm:col-span-1 rounded-lg border-gray-300 text-sm">
                                <option value="">No milestone</option>
                                @foreach ($milestones as $m)
                                    <option value="{{ $m->id }}" @selected((int) old('milestone_id') === $m->id)>{{ $m->title }}</option>
                                @endforeach
                            </select>
                            <button class="sm:col-span-6 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Create task</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="flex gap-4 overflow-x-auto pb-4">
                @foreach ($statuses as $status)
                    @php $items = $byStatus->get($status, collect()); @endphp
                    <div class="w-72 shrink-0 rounded-2xl border-t-4 p-3 shadow-sm {{ $columnStyles[$status] }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-gray-700">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            <span class="text-xs text-gray-500">{{ $items->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($items as $task)
                                <div class="bg-white rounded-xl p-3 shadow-sm">
                                    <a href="{{ route('projects.tasks.show', [$project, $task]) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $task->title }}</a>
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <x-badge :color="$pColors[$task->priority]">{{ ucfirst($task->priority) }}</x-badge>
                                        @if ($task->milestone) <x-badge color="violet">{{ $task->milestone->title }}</x-badge> @endif
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">
                                        {{ $task->assignee?->name ?? 'Unassigned' }}
                                        @if ($task->due_date)
                                            · <span class="{{ $task->isOverdue() ? 'text-rose-600 font-medium' : '' }}">{{ $task->due_date->format('d M') }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $task->comments_count }} comments · {{ $task->attachments_count }} files</p>

                                    @if ($canManage || $task->assignee_id === $me)
                                        <form method="POST" action="{{ route('projects.tasks.status', [$project, $task]) }}" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs">
                                                @foreach ($statuses as $s)
                                                    <option value="{{ $s }}" @selected($task->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 text-center py-4">No tasks</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
