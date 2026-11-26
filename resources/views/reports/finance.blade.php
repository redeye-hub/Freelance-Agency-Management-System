<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Finance Dashboard</h2>
    </x-slot>

    @php
        $query = ['from' => $r['from']->toDateString(), 'to' => $r['to']->toDateString()];
        $maxMonth = max(1, collect($r['monthly'])->max('amount'));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap items-end gap-3">
                <label class="text-xs text-gray-500">From
                    <input type="date" name="from" value="{{ $query['from'] }}" class="mt-0.5 block rounded-lg border-gray-300 text-sm">
                </label>
                <label class="text-xs text-gray-500">To
                    <input type="date" name="to" value="{{ $query['to'] }}" class="mt-0.5 block rounded-lg border-gray-300 text-sm">
                </label>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Apply</button>
                <div class="ml-auto flex gap-2 text-sm">
                    <a href="{{ route('reports.finance.export', $query + ['format' => 'csv']) }}" class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Export CSV</a>
                    <a href="{{ route('reports.finance.export', $query + ['format' => 'pdf']) }}" class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100">Export PDF</a>
                </div>
            </form>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Revenue (paid)</p><p class="text-2xl font-bold text-emerald-600">${{ number_format($r['revenue'], 2) }}</p><p class="text-xs text-gray-400">Invoiced ${{ number_format($r['invoiced'], 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Freelancer cost</p><p class="text-2xl font-bold text-gray-800">${{ number_format($r['cost'], 2) }}</p><p class="text-xs text-gray-400">Agency commission ${{ number_format($r['commission'], 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Profit</p><p class="text-2xl font-bold {{ $r['profit'] < 0 ? 'text-rose-600' : 'text-indigo-600' }}">${{ number_format($r['profit'], 2) }}</p><p class="text-xs text-gray-400">Revenue minus freelancer cost</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Outstanding invoices</p><p class="text-2xl font-bold text-sky-600">${{ number_format($r['outstanding'], 2) }}</p><p class="text-xs {{ $r['overdue'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">${{ number_format($r['overdue'], 2) }} overdue ({{ $r['overdue_count'] }})</p></div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Paid revenue, last 6 months</h3>
                    <div class="mt-4 flex items-end gap-3 h-44">
                        @foreach ($r['monthly'] as $m)
                            <div class="flex-1 flex flex-col items-center justify-end h-full">
                                <span class="text-xs text-gray-500 mb-1">${{ number_format($m['amount'], 0) }}</span>
                                <div class="w-full rounded-t-lg bg-indigo-500" style="height: {{ max(2, round($m['amount'] / $maxMonth * 100)) }}%"></div>
                                <span class="text-xs text-gray-400 mt-1">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Needs attention</h3>
                    <p class="mt-2 text-sm text-gray-600">Payouts waiting: <strong>${{ number_format($r['payouts_pending'], 2) }}</strong>
                        @if (Route::has('payouts.index')) <a href="{{ route('payouts.index') }}" class="text-indigo-600 hover:underline">open</a> @endif
                    </p>
                    <h4 class="mt-4 text-sm font-medium text-gray-700">Overdue invoices</h4>
                    <ul class="mt-1 divide-y divide-gray-100 text-sm">
                        @forelse ($r['overdue_invoices'] as $i)
                            <li class="py-1.5 flex justify-between gap-2">
                                <span>{{ $i->number }} <span class="text-gray-400">{{ $i->client->company_name }}</span></span>
                                <span class="text-rose-600">{{ $i->due_date->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="py-1.5 text-gray-400">None. Nice.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <div class="px-6 pt-5"><h3 class="font-semibold text-gray-800">Profit per project</h3></div>
                <table class="mt-3 min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr><th class="px-4 py-3">Project</th><th class="px-4 py-3">Client</th><th class="px-4 py-3 text-right">Invoiced</th><th class="px-4 py-3 text-right">Revenue (paid)</th><th class="px-4 py-3 text-right">Freelancer cost</th><th class="px-4 py-3 text-right">Commission</th><th class="px-4 py-3 text-right">Profit</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($r['projects'] as $p)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $p['project'] }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $p['client'] }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($p['invoiced'], 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($p['revenue'], 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($p['cost'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">${{ number_format($p['commission'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $p['profit'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">${{ number_format($p['profit'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No project activity in this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($r['projects']->isNotEmpty())
                        <tfoot class="border-t font-semibold">
                            <tr><td class="px-4 py-3" colspan="2">Total</td><td class="px-4 py-3 text-right">${{ number_format($r['invoiced'], 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($r['revenue'], 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($r['cost'], 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($r['commission'], 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($r['profit'], 2) }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <p class="text-xs text-gray-400">Revenue is the invoice subtotal (tax excluded) of invoices paid in the period. Freelancer cost is the net pay for hours in approved timesheets in the period. Outstanding and overdue show today's position.</p>
        </div>
    </div>
</x-app-layout>
