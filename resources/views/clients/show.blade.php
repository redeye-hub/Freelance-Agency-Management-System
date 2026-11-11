<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $client->company_name }}</h2>
            <div class="flex gap-3 text-sm">
                <a href="{{ route('clients.index') }}" class="text-indigo-600 hover:underline">&larr; All clients</a>
                <a href="{{ route('clients.edit', $client) }}" class="px-3 py-1.5 rounded-lg bg-sky-600 text-white hover:bg-sky-700">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Company details</h3>
                        <x-status-badge :value="$client->status" />
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-gray-500">Contact person</dt><dd class="font-medium text-gray-800">{{ $client->contact_name }}</dd></div>
                        <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-800">{{ $client->email }}</dd></div>
                        <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-800">{{ $client->phone ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Website</dt><dd class="font-medium text-gray-800">{{ $client->website ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Tax / VAT ID</dt><dd class="font-medium text-gray-800">{{ $client->tax_id ?: '—' }}</dd></div>
                    </dl>
                </div>

                <div class="space-y-6">
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
                        <h3 class="font-semibold text-emerald-800">Billing address</h3>
                        <p class="mt-3 text-sm text-emerald-900 whitespace-pre-line">{{ $client->billing_address ?: 'Not added yet.' }}</p>
                        <p class="mt-1 text-sm text-emerald-900">{{ collect([$client->city, $client->country])->filter()->implode(', ') }}</p>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                        <h3 class="font-semibold text-amber-800">Notes</h3>
                        <p class="mt-3 text-sm text-amber-900 whitespace-pre-line">{{ $client->notes ?: 'No notes.' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Project history</h3>
                <div class="mt-3 rounded-xl border-2 border-dashed border-violet-200 bg-violet-50 p-6 text-center text-sm text-violet-700">
                    No projects yet. Projects created in Module 2 will be listed here.
                </div>
            </div>

            @if ($client->leads->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800">Original lead</h3>
                    <ul class="mt-3 divide-y text-sm">
                        @foreach ($client->leads as $lead)
                            <li class="py-2 flex items-center justify-between">
                                <a href="{{ route('leads.show', $lead) }}" class="text-indigo-600 hover:underline">{{ $lead->name }} – {{ $lead->service ?: 'inquiry' }}</a>
                                <span class="text-gray-400">{{ $lead->created_at->format('d M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>