<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoice {{ $invoice->number }}</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('portal.invoices') }}" class="text-sm text-indigo-600 hover:underline">&larr; My invoices</a>

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-2">
            <p><span class="text-gray-500">Number:</span> {{ $invoice->number }}</p>
            <p><span class="text-gray-500">Status:</span> {{ ucfirst($invoice->status) }}</p>
        </div>

        @if ($invoice->status !== 'paid' && $invoice->public_token)
            <a href="{{ route('pay.show', $invoice->public_token) }}"
               class="inline-block px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Pay now</a>
        @endif
    </div>
</x-app-layout>