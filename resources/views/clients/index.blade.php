<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clients</h2>
            <a href="{{ route('clients.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New client</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search company, contact or email"
                       class="flex-1 min-w-[12rem] rounded-lg border-gray-300 text-sm">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('clients.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">Company</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($clients as $client)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $client->company_name }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-gray-800">{{ $client->contact_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $client->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ collect([$client->city, $client->country])->filter()->implode(', ') ?: '—' }}</td>
                                <td class="px-4 py-3"><x-status-badge :value="$client->status" /></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:underline">View</a>
                                    <a href="{{ route('clients.edit', $client) }}" class="ml-3 text-sky-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No clients found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $clients->links() }}
        </div>
    </div>
</x-app-layout>