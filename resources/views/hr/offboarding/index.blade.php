<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Freelancer Offboarding</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email"
                       class="flex-1 min-w-[200px] rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach (['active', 'suspended', 'archived'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('hr.offboarding.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Freelancer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3">Change status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($freelancers as $f)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800">{{ $f->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $f->email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-badge :value="$f->status" />
                                    @if ($f->offboarded_at)
                                        <p class="mt-1 text-xs text-gray-400">{{ $f->offboarded_at->format('d M Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs">{{ $f->offboarding_reason ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('hr.offboarding.update', $f) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="rounded-lg border-gray-300 text-xs">
                                            @foreach (['active', 'suspended', 'archived'] as $s)
                                                <option value="{{ $s }}" @selected($f->status === $s)>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="reason" placeholder="Reason (required unless active)"
                                               class="rounded-lg border-gray-300 text-xs w-52">
                                        <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs hover:bg-indigo-700">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No freelancers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $freelancers->links() }}
        </div>
    </div>
</x-app-layout>
