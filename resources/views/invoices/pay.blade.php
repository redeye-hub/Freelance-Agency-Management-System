<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Pay invoice {{ $invoice->number }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 via-white to-indigo-50 min-h-screen text-gray-800">
    <div class="max-w-xl mx-auto px-6 py-10 space-y-4">
        <div class="p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-800 text-sm">
            <strong>SANDBOX DEMO.</strong> No real money moves. Never enter a real card number here.
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-sm text-gray-500">Invoice {{ $invoice->number }}</p>
            <h1 class="text-xl font-bold text-gray-900">{{ $invoice->project->name }}</h1>
            <p class="text-sm text-gray-500">For {{ $invoice->client->company_name }} · due {{ $invoice->due_date->format('d M Y') }}</p>

            <ul class="mt-4 divide-y divide-gray-100 text-sm">
                @foreach ($invoice->items as $it)
                    <li class="py-2 flex justify-between gap-3"><span>{{ $it->description }}</span><span class="font-medium">${{ number_format((float) $it->amount, 2) }}</span></li>
                @endforeach
                <li class="py-2 flex justify-between text-gray-500"><span>Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</span><span>${{ number_format((float) $invoice->tax_amount, 2) }}</span></li>
                <li class="py-2 flex justify-between text-lg font-bold"><span>Total</span><span class="text-indigo-600">${{ number_format((float) $invoice->total, 2) }}</span></li>
            </ul>
        </div>

        @if ($invoice->status === 'paid')
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">This invoice is paid. Thank you.</div>
        @elseif (! $invoice->isPayable())
            <div class="p-4 rounded-xl bg-gray-100 text-gray-600 text-sm">This invoice cannot be paid online right now.</div>
        @else
            <form method="POST" action="{{ route('pay.store', $invoice->public_token) }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-3">
                @csrf
                <h2 class="font-semibold text-gray-800">Pay with a test card</h2>
                <div>
                    <x-input-label for="card_name" value="Name on card" />
                    <x-text-input id="card_name" name="card_name" type="text" class="mt-1 block w-full" :value="old('card_name')" required />
                    <x-input-error class="mt-1" :messages="$errors->get('card_name')" />
                </div>
                <div>
                    <x-input-label for="card_number" value="Test card number" />
                    <x-text-input id="card_number" name="card_number" type="text" inputmode="numeric" autocomplete="off" class="mt-1 block w-full" placeholder="{{ $successCard }}" required />
                    <x-input-error class="mt-1" :messages="$errors->get('card_number')" />
                </div>
                <button class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Pay ${{ number_format((float) $invoice->total, 2) }} (sandbox)</button>
                <p class="text-xs text-gray-500">
                    Use <code>{{ $successCard }}</code> to simulate a successful payment, or <code>{{ $declineCard }}</code> to simulate a decline. Any other number is rejected. The card number is never saved.
                </p>
            </form>
        @endif
    </div>
</body>
</html>
