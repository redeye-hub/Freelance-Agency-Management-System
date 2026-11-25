<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Contracts</h2>
    </x-slot>

    @php $colors = ['sent' => 'amber', 'accepted' => 'emerald', 'declined' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            @forelse ($contracts as $c)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $c->assignment->project->name }}</p>
                            <p class="text-xs text-gray-500">{{ $c->number }} · {{ $c->assignment->project->client->company_name }}</p>
                        </div>
                        <x-badge :color="$colors[$c->status]">{{ ucfirst($c->status) }}</x-badge>
                    </div>

                    <dl class="mt-4 grid sm:grid-cols-4 gap-3 text-sm">
                        <div><dt class="text-gray-500">Rate</dt><dd class="font-medium">${{ number_format((float) $c->hourly_rate, 2) }}/hr</dd></div>
                        <div><dt class="text-gray-500">Commission</dt><dd class="font-medium">{{ $c->commission_percent }}%</dd></div>
                        <div><dt class="text-gray-500">Hours/week</dt><dd class="font-medium">{{ $c->weekly_hours }}</dd></div>
                        <div><dt class="text-gray-500">Period</dt><dd class="font-medium">{{ $c->start_date->format('d M Y') }} to {{ $c->end_date?->format('d M Y') ?? 'completion' }}</dd></div>
                    </dl>

                    <details class="mt-3 text-sm">
                        <summary class="cursor-pointer text-indigo-600">Read the terms</summary>
                        <p class="mt-2 whitespace-pre-line text-gray-700 bg-gray-50 rounded-xl p-4">{{ $c->terms }}</p>
                    </details>

                    <div class="mt-4 flex flex-wrap items-start gap-2">
                        <a href="{{ route('my.contracts.pdf', $c) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Download PDF</a>
                        @if ($c->status === 'sent')
                            <form method="POST" action="{{ route('my.contracts.accept', $c) }}" onsubmit="return confirm('Accept this contract?')">
                                @csrf
                                @method('PATCH')
                                <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Accept</button>
                            </form>
                            <form method="POST" action="{{ route('my.contracts.decline', $c) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="response_note" required placeholder="Why are you declining?" class="rounded-lg border-gray-300 text-sm w-60">
                                <button class="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm hover:bg-rose-700">Decline</button>
                            </form>
                        @elseif ($c->responded_at)
                            <p class="text-xs text-gray-500 self-center">Answered {{ $c->responded_at->format('d M Y, H:i') }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="bg-white rounded-2xl shadow-sm p-6 text-sm text-gray-400">You have no contracts yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
