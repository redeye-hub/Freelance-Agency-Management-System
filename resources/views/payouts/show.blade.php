<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payout {{ $payout->number }}</h2>
            <a href="{{ route('payouts.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; All payouts</a>
        </div>
    </x-slot>

    @php $colors = ['pending' => 'amber', 'approved' => 'sky', 'paid' => 'emerald', 'rejected' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-input-error :messages="$errors->get('status')" />
            <x-input-error :messages="$errors->get('method')" />
            <x-input-error :messages="$errors->get('reference')" />
            <x-input-error :messages="$errors->get('note')" />

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-semibold text-gray-800">{{ $payout->user->name }}</p>
                        <p class="text-xs text-gray-500">Requested by {{ $payout->requester?->name ?? 'N/A' }} on {{ $payout->created_at->format('d M Y') }}</p>
                    </div>
                    <x-badge :color="$colors[$payout->status]">{{ ucfirst($payout->status) }}</x-badge>
                </div>

                <table class="mt-4 min-w-full text-sm">
                    <thead class="text-left text-gray-500 border-b">
                        <tr><th class="py-2">Project</th><th class="py-2 text-right">Hours</th><th class="py-2 text-right">Rate</th><th class="py-2 text-right">Gross</th><th class="py-2 text-right">Commission</th><th class="py-2 text-right">Net</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($payout->lines as $l)
                            <tr>
                                <td class="py-2">{{ $l->project->name }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $l->hours, 2) }}</td>
                                <td class="py-2 text-right">${{ number_format((float) $l->hourly_rate, 2) }}</td>
                                <td class="py-2 text-right">${{ number_format((float) $l->gross, 2) }}</td>
                                <td class="py-2 text-right text-gray-500">${{ number_format((float) $l->commission, 2) }} ({{ rtrim(rtrim(number_format((float) $l->commission_percent, 2), '0'), '.') }}%)</td>
                                <td class="py-2 text-right font-semibold">${{ number_format((float) $l->net, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-semibold">
                            <td class="py-2">Total</td>
                            <td class="py-2 text-right">{{ number_format((float) $payout->hours, 2) }}</td>
                            <td></td>
                            <td class="py-2 text-right">${{ number_format((float) $payout->gross, 2) }}</td>
                            <td class="py-2 text-right">${{ number_format((float) $payout->commission, 2) }}</td>
                            <td class="py-2 text-right text-indigo-600">${{ number_format((float) $payout->net, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if ($payout->status === 'paid')
                    <p class="mt-4 text-sm text-emerald-700 bg-emerald-50 rounded-xl p-3">
                        Paid on {{ $payout->paid_at->format('d M Y') }} via {{ ucfirst(str_replace('_', ' ', $payout->method)) }}, reference {{ $payout->reference }}.
                    </p>
                @elseif ($payout->status === 'rejected')
                    <p class="mt-4 text-sm text-rose-700 bg-rose-50 rounded-xl p-3">Rejected by {{ $payout->reviewer?->name }}: {{ $payout->note }}</p>
                @endif
            </div>

            @if ($payout->status === 'pending')
                <div class="bg-white rounded-2xl shadow-sm p-5 flex flex-wrap gap-3 items-start">
                    <form method="POST" action="{{ route('payouts.approve', $payout) }}">
                        @csrf
                        @method('PATCH')
                        <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Approve payout</button>
                    </form>
                    <form method="POST" action="{{ route('payouts.reject', $payout) }}" class="flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="note" required placeholder="Reason for rejecting" class="rounded-lg border-gray-300 text-sm w-64">
                        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Reject</button>
                    </form>
                </div>
            @elseif ($payout->status === 'approved')
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Record the payment</h3>
                    <form method="POST" action="{{ route('payouts.paid', $payout) }}" class="grid sm:grid-cols-3 gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="method" class="rounded-lg border-gray-300 text-sm">
                            @foreach ($methods as $m)
                                <option value="{{ $m }}">{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="reference" required placeholder="Transaction id or receipt no." class="rounded-lg border-gray-300 text-sm">
                        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Mark as paid</button>
                    </form>
                    <form method="POST" action="{{ route('payouts.reject', $payout) }}" class="mt-3 flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="note" required placeholder="Reason for rejecting" class="rounded-lg border-gray-300 text-sm w-64">
                        <button class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm hover:bg-rose-100">Reject instead</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
