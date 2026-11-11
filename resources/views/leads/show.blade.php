<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lead: {{ $lead->name }}</h2>
            <a href="{{ route('leads.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to pipeline</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-3 gap-6">
                {{-- details --}}
                <div class="md:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Inquiry details</h3>
                        <x-stage-badge :stage="$lead->stage" />
                    </div>
                    <dl class="mt-4 grid sm:grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Name</dt><dd class="font-medium text-gray-800">{{ $lead->name }}</dd></div>
                        <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-800">{{ $lead->email }}</dd></div>
                        <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-800">{{ $lead->phone ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Company</dt><dd class="font-medium text-gray-800">{{ $lead->company ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Service</dt><dd class="font-medium text-gray-800">{{ $lead->service ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Received</dt><dd class="font-medium text-gray-800">{{ $lead->created_at->format('d M Y, H:i') }}</dd></div>
                    </dl>
                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Message</p>
                        <p class="mt-1 text-sm text-gray-800 whitespace-pre-line bg-sky-50 rounded-xl p-4">{{ $lead->message ?: '—' }}</p>
                    </div>
                </div>

                {{-- actions --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm p-5">
                        <h3 class="font-semibold text-gray-800 mb-3">Pipeline stage</h3>
                        <form method="POST" action="{{ route('leads.stage', $lead) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="stage" class="flex-1 rounded-lg border-gray-300 text-sm">
                                @foreach ($stages as $s)
                                    <option value="{{ $s }}" @selected($lead->stage === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Move</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-5">
                        <h3 class="font-semibold text-gray-800 mb-3">Assign &amp; notes</h3>
                        <form method="POST" action="{{ route('leads.update', $lead) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <select name="assigned_to" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Unassigned</option>
                                @foreach ($managers as $m)
                                    <option value="{{ $m->id }}" @selected($lead->assigned_to === $m->id)>{{ $m->name }}</option>
                                @endforeach
                            </select>
                            <textarea name="notes" rows="4" placeholder="Internal notes"
                                      class="w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $lead->notes) }}</textarea>
                            <button class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700">Save</button>
                        </form>
                    </div>

                    <div class="rounded-2xl p-5 {{ $lead->client_id ? 'bg-emerald-50 border border-emerald-200' : 'bg-white shadow-sm' }}">
                        <h3 class="font-semibold text-gray-800 mb-2">Client conversion</h3>
                        @if ($lead->client_id)
                            <p class="text-sm text-emerald-700">This lead is already a client.</p>
                            <a href="{{ route('clients.show', $lead->client_id) }}" class="inline-block mt-2 text-sm font-medium text-indigo-600 hover:underline">Open client profile &rarr;</a>
                        @elseif ($lead->stage === 'won')
                            <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                @csrf
                                <button class="w-full px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Convert to client</button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500">Move this lead to <strong>Won</strong> to enable conversion.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>