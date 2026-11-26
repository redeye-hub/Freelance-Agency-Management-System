<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Earnings</h2>
    </x-slot>

    @php $colors = ['pending' => 'amber', 'approved' => 'sky', 'paid' => 'emerald', 'rejected' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />
            <x-input-error :messages="$errors->get('payout')" />

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <p class="text-sm text-gray-500">Ready to be paid out (after commission)</p>
                    <p class="text-3xl font-bold text-indigo-600">${{ number_format($unpaidNet, 2) }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <p class="text-sm text-gray-500">Paid to you so far</p>
                    <p class="text-3xl font-bold text-emerald-600">${{ number_format($lifetimePaid, 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Approved hours not paid out yet</h3>
                <p class="text-xs text-gray-400">Hours count after your weekly timesheet is approved.</p>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500 border-b">
                            <tr><th class="py-2">Project</th><th class="py-2 text-right">Hours</th><th class="py-2 text-right">Rate</th><th class="py-2 text-right">Gross</th><th class="py-2 text-right">Commission</th><th class="py-2 text-right">You receive</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($summary as $r)
                                <tr>
                                    <td class="py-2">{{ $r['project'] }}</td>
                                    <td class="py-2 text-right">{{ number_format($r['hours'], 2) }}</td>
                                    <td class="py-2 text-right">${{ number_format($r['rate'], 2) }}</td>
                                    <td class="py-2 text-right">${{ number_format($r['gross'], 2) }}</td>
                                    <td class="py-2 text-right text-gray-500">-${{ number_format($r['commission'], 2) }} ({{ rtrim(rtrim(number_format($r['commission_percent'], 2), '0'), '.') }}%)</td>
                                    <td class="py-2 text-right font-semibold">${{ number_format($r['net'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center text-gray-400">Nothing waiting. Submit your timesheet and get it approved first.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($summary->isNotEmpty())
                    <form method="POST" action="{{ route('earnings.request') }}" class="mt-4" onsubmit="return confirm('Request a payout of ${{ number_format($unpaidNet, 2) }}?')">
                        @csrf
                        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Request payout of ${{ number_format($unpaidNet, 2) }}</button>
                    </form>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Payout history</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500 border-b">
                            <tr><th class="py-2">Number</th><th class="py-2">Requested</th><th class="py-2 text-right">Hours</th><th class="py-2 text-right">Net</th><th class="py-2">Status</th><th class="py-2">Paid via</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payouts as $p)
                                <tr>
                                    <td class="py-2 font-medium">{{ $p->number }}</td>
                                    <td class="py-2">{{ $p->created_at->format('d M Y') }}</td>
                                    <td class="py-2 text-right">{{ number_format((float) $p->hours, 2) }}</td>
                                    <td class="py-2 text-right font-semibold">${{ number_format((float) $p->net, 2) }}</td>
                                    <td class="py-2"><x-badge :color="$colors[$p->status]">{{ ucfirst($p->status) }}</x-badge>
                                        @if ($p->status === 'rejected' && $p->note) <span class="block text-xs text-rose-600">{{ $p->note }}</span> @endif
                                    </td>
                                    <td class="py-2 text-gray-500">{{ $p->status === 'paid' ? ucfirst(str_replace('_', ' ', $p->method)) . ', ' . $p->reference : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center text-gray-400">No payouts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
