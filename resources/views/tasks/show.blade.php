<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Task: {{ $task->title }}</h2>
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to board</a>
        </div>
    </x-slot>

    @php $pColors = ['low' => 'gray', 'medium' => 'amber', 'high' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <div class="flex items-center gap-2">
                            <x-badge :color="$pColors[$task->priority]">{{ ucfirst($task->priority) }}</x-badge>
                            <x-badge color="indigo">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</x-badge>
                            @if ($task->isOverdue()) <x-badge color="rose">Overdue</x-badge> @endif
                        </div>
                        <dl class="mt-4 grid sm:grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-gray-500">Assignee</dt><dd class="font-medium text-gray-800">{{ $task->assignee?->name ?? 'Unassigned' }}</dd></div>
                            <div><dt class="text-gray-500">Due</dt><dd class="font-medium text-gray-800">{{ $task->due_date?->format('d M Y') ?? 'N/A' }}</dd></div>
                            <div><dt class="text-gray-500">Milestone</dt><dd class="font-medium text-gray-800">{{ $task->milestone?->title ?? 'None' }}</dd></div>
                            <div><dt class="text-gray-500">Created by</dt><dd class="font-medium text-gray-800">{{ $task->creator?->name ?? 'N/A' }}</dd></div>
                        </dl>
                        @if ($task->description)
                            <p class="mt-4 text-sm text-gray-700 whitespace-pre-line bg-sky-50 rounded-xl p-4">{{ $task->description }}</p>
                        @endif
                    </div>

                    {{-- comments --}}
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800">Comments ({{ $task->comments->count() }})</h3>
                        <ul class="mt-3 space-y-3 text-sm">
                            @forelse ($task->comments as $c)
                                <li class="rounded-xl bg-gray-50 p-3">
                                    <p class="text-xs text-gray-500"><strong class="text-gray-700">{{ $c->user->name }}</strong> · {{ $c->created_at->diffForHumans() }}</p>
                                    <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $c->body }}</p>
                                </li>
                            @empty
                                <li class="text-gray-400">No comments yet.</li>
                            @endforelse
                        </ul>
                        <form method="POST" action="{{ route('projects.tasks.comments.store', [$project, $task]) }}" class="mt-4 space-y-2">
                            @csrf
                            <textarea name="body" rows="3" required placeholder="Write a comment" class="w-full rounded-lg border-gray-300 text-sm">{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" />
                            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Post comment</button>
                        </form>
                    </div>

                    {{-- files --}}
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800">Files ({{ $task->attachments->count() }})</h3>
                        <ul class="mt-3 divide-y divide-gray-100 text-sm">
                            @forelse ($task->attachments as $f)
                                <li class="py-2 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('projects.tasks.attachments.download', [$project, $task, $f]) }}" class="text-indigo-600 hover:underline break-all">{{ $f->original_name }}</a>
                                        <p class="text-xs text-gray-400">{{ $f->humanSize() }} · {{ $f->user?->name }} · {{ $f->created_at->format('d M Y') }}</p>
                                    </div>
                                    @if ($canManage || $f->user_id === auth()->id())
                                        <form method="POST" action="{{ route('projects.tasks.attachments.destroy', [$project, $task, $f]) }}" onsubmit="return confirm('Delete this file?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-rose-600 hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </li>
                            @empty
                                <li class="py-2 text-gray-400">No files yet.</li>
                            @endforelse
                        </ul>
                        <form method="POST" action="{{ route('projects.tasks.attachments.store', [$project, $task]) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap gap-2 items-center">
                            @csrf
                            <input type="file" name="file" required class="text-sm">
                            <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Upload</button>
                        </form>
                        <x-input-error class="mt-1" :messages="$errors->get('file')" />
                        <p class="mt-2 text-xs text-gray-400">PDF, images, Office files, ZIP or TXT, up to 10 MB.</p>
                    </div>
                </div>

                {{-- side actions --}}
                <div class="space-y-4">
                    @if ($canMove)
                        <div class="bg-white rounded-2xl shadow-sm p-5">
                            <h3 class="font-semibold text-gray-800 mb-3">Move task</h3>
                            <form method="POST" action="{{ route('projects.tasks.status', [$project, $task]) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" @selected($task->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Move</button>
                            </form>
                        </div>
                    @endif

                    @if ($canManage)
                        <div class="bg-white rounded-2xl shadow-sm p-5">
                            <h3 class="font-semibold text-gray-800 mb-3">Edit task</h3>
                            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="title" required value="{{ old('title', $task->title) }}" class="w-full rounded-lg border-gray-300 text-sm">
                                <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description', $task->description) }}</textarea>
                                <select name="assignee_id" class="w-full rounded-lg border-gray-300 text-sm">
                                    <option value="">Unassigned</option>
                                    @foreach ($team as $t)
                                        <option value="{{ $t->id }}" @selected($task->assignee_id === $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <select name="priority" class="w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($priorities as $p)
                                        <option value="{{ $p }}" @selected($task->priority === $p)>{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 text-sm">
                                <select name="milestone_id" class="w-full rounded-lg border-gray-300 text-sm">
                                    <option value="">No milestone</option>
                                    @foreach ($milestones as $m)
                                        <option value="{{ $m->id }}" @selected($task->milestone_id === $m->id)>{{ $m->title }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('assignee_id')" />
                                <button class="w-full px-3 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700">Save changes</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Delete this task and its files?')">
                            @csrf
                            @method('DELETE')
                            <button class="w-full px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm hover:bg-rose-100">Delete task</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
