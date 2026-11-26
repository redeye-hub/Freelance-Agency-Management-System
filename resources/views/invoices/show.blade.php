<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoice {{ $invoice->number }}</h2>
            <a href="{{ route('invoices.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; All invoices</a>
        </div>
    </x-slot>

    @php
        $colors = ['draft' => 'gray', 'sent' => 'sky', 'paid' => 'emerald', 'void' => 'rose', 'overdue' => 'amber'];
        $st = $invoice->displayStatus();
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-input-error :messages="$errors->get('status')" />

            <div class="grid md:grid-cols-3 gap-6">
                <div class="md:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold text-gray-800">{{ $invoice->client->company_name }}</p>
                            <p class="text-sm text-gray-500">{{ $invoice->project->name }}</p>
                        </div>
                        <x-badge :color="$colors[$st]">{{ ucfirst($st) }}</x-badge>
                    </div>

                    <dl class="mt-4 grid sm:grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-gray-500">Issued</dt><dd class="font-medium">{{ $invoice->issue_date->format('d M Y') }}</dd></div>
                        <div><dt class="text-gray-500">Due</dt><dd class="font-medium {{ $invoice->isOverdue() ? 'text-rose-600' : '' }}">{{ $invoice->due_date->format('d M Y') }}</dd></div>
                        <div><dt class="text-gray-500">Paid</dt><dd class="font-medium">{{ $invoice->paid_at?->format('d M Y') ?? 'N/A' }}</dd></div>
                    </dl>

                    <table class="mt-5 min-w-full text-sm">
                        <thead class="text-left text-gray-500 border-b"><tr><th class="py-2">Description</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Unit price</th><th class="py-2 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($invoice->items as $it)
                                <tr>
                                    <td class="py-2 pr-3">{{ $it->description }}</td>
                                    <td class="py-2 text-right">{{ rtrim(rtrim(number_format((float) $it->quantity, 2), '0'), '.') }}</td>
                                    <td class="py-2 text-right">${{ number_format((float) $it->unit_price, 2) }}</td>
                                    <td class="py-2 text-right font-medium">${{ number_format((float) $it->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="text-sm">
                            <tr><td colspan="3" class="pt-3 text-right text-gray-500">Subtotal</td><td class="pt-3 text-right">${{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
                            <tr><td colspan="3" class="text-right text-gray-500">Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</td><td class="text-right">${{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>
                            <tr><td colspan="3" class="pt-1 text-right font-semibold">Total</td><td class="pt-1 text-right text-lg font-bold text-indigo-600">${{ number_format((float) $invoice->total, 2) }}</td></tr>
                        </tfoot>
                    </table>
                    @if ($invoice->notes) <p class="mt-4 text-sm text-gray-600 whitespace-pre-line">{{ $invoice->notes }}</p> @endif
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm p-5 space-y-2">
                        <h3 class="font-semibold text-gray-800">Actions</h3>
                        <a href="{{ route('invoices.pdf', $invoice) }}" class="block text-center px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Download PDF</a>

                        @if (in_array($invoice->status, ['draft', 'sent']))
                            <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                                @csrf
                                <button class="w-full px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">{{ $invoice->status === 'draft' ? 'Email to client' : 'Resend email' }}</button>
                            </form>
                            <form method="POST" action="{{ route('invoices.paid', $invoice) }}" class="space-y-1" onsubmit="return confirm('Record this invoice as paid?')">
                                @csrf
                                <input type="text" name="reference" placeholder="Bank reference (optional)" class="w-full rounded-lg border-gray-300 text-sm">
                                <button class="w-full px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Mark as paid</button>
                            </form>
                            <form method="POST" action="{{ route('invoices.void', $invoice) }}" onsubmit="return confirm('Void this invoice? Its work becomes billable again.')">
                                @csrf
                                <button class="w-full px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm hover:bg-rose-100">Void invoice</button>
                            </form>
                        @endif

                        @if ($invoice->status === 'sent')
                            <p class="text-xs text-gray-400 break-all">Client pay link (sandbox): <a class="text-indigo-600 hover:underline" href="{{ route('pay.show', $invoice->public_token) }}" target="_blank">open</a></p>
                        @endif
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-5">
                        <h3 class="font-semibold text-gray-800 mb-2">Payments</h3>
                        <ul class="space-y-2 text-sm">
                            @forelse ($invoice->payments as $p)
                                <li class="rounded-xl bg-gray-50 p-3">
                                    <p><x-badge :color="$p->status === 'succeeded' ? 'emerald' : 'rose'">{{ ucfirst($p->status) }}</x-badge> ${{ number_format((float) $p->amount, 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ $p->method === 'manual' ? 'Recorded manually' : 'Sandbox card' . ($p->card_last4 ? ' ending ' . $p->card_last4 : '') }} · {{ $p->created_at->format('d M Y H:i') }}</p>
                                    @if ($p->reference) <p class="text-xs text-gray-400 break-all">Ref: {{ $p->reference }}</p> @endif
                                    @if ($p->failure_reason) <p class="text-xs text-rose-600">{{ $p->failure_reason }}</p> @endif
                                </li>
                            @empty
                                <li class="text-gray-400">No payments yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
