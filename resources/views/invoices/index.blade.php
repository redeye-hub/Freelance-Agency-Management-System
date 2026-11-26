<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ New invoice</a>
        </div>
    </x-slot>

    @php
        $colors = ['draft' => 'gray', 'sent' => 'sky', 'paid' => 'emerald', 'void' => 'rose', 'overdue' => 'amber'];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Outstanding</p><p class="text-2xl font-bold text-sky-600">${{ number_format($outstanding, 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Overdue</p><p class="text-2xl font-bold text-amber-600">${{ number_format($overdue, 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Paid this month</p><p class="text-2xl font-bold text-emerald-600">${{ number_format($paidThisMonth, 2) }}</p></div>
            </div>

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Invoice number or client" class="flex-1 min-w-[12rem] rounded-lg border-gray-300 text-sm">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'sent', 'overdue', 'paid', 'void'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('invoices.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr><th class="px-4 py-3">Number</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Due</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($invoices as $i)
                            @php $st = $i->displayStatus(); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $i->number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $i->client->company_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $i->project->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $i->due_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 font-medium">${{ number_format((float) $i->total, 2) }}</td>
                                <td class="px-4 py-3"><x-badge :color="$colors[$st]">{{ ucfirst($st) }}</x-badge></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('invoices.show', $i) }}" class="text-indigo-600 hover:underline">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
