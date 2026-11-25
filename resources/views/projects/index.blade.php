<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projects</h2>
            <a href="{{ route('projects.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New project</a>
        </div>
    </x-slot>

    @php
        $colors = ['planning' => 'sky', 'active' => 'emerald', 'on_hold' => 'amber', 'completed' => 'indigo', 'cancelled' => 'rose'];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search project name"
                       class="flex-1 min-w-[12rem] rounded-lg border-gray-300 text-sm">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">Project</th>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Budget</th>
                            <th class="px-4 py-3">Deadline</th>
                            <th class="px-4 py-3">Team</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($projects as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $p->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $p->client->company_name }}</td>
                                <td class="px-4 py-3 text-gray-600">${{ number_format((float) $p->budget, 2) }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($p->deadline)
                                        <span class="{{ $p->deadline->isPast() && $p->status !== 'completed' ? 'text-rose-600 font-medium' : '' }}">{{ $p->deadline->format('d M Y') }}</span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $p->active_assignments_count }}</td>
                                <td class="px-4 py-3"><x-badge :color="$colors[$p->status]">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</x-badge></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if (Route::has('projects.board'))
                                        <a href="{{ route('projects.board', $p) }}" class="text-sky-600 hover:underline mr-3">Board</a>
                                    @endif
                                    <a href="{{ route('projects.show', $p) }}" class="text-indigo-600 hover:underline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No projects yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
