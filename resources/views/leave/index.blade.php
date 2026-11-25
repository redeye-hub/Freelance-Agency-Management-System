<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Leave</h2>
    </x-slot>

    @php $colors = ['pending' => 'amber', 'approved' => 'emerald', 'rejected' => 'rose', 'cancelled' => 'gray']; @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Request leave</h3>
                <form method="POST" action="{{ route('leave.store') }}" class="grid sm:grid-cols-4 gap-3">
                    @csrf
                    <select name="type" class="rounded-lg border-gray-300 text-sm">
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(old('type') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="start_date" required min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" class="rounded-lg border-gray-300 text-sm">
                    <input type="date" name="end_date" required min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" class="rounded-lg border-gray-300 text-sm">
                    <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Send request</button>
                    <input type="text" name="reason" maxlength="1000" placeholder="Reason (optional)" value="{{ old('reason') }}" class="sm:col-span-4 rounded-lg border-gray-300 text-sm">
                </form>
                <x-input-error class="mt-1" :messages="$errors->get('start_date')" />
                <x-input-error class="mt-1" :messages="$errors->get('end_date')" />
                <p class="mt-2 text-xs text-gray-400">While your leave is approved and running, you appear as unavailable in freelancer search.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr><th class="px-4 py-3">Dates</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Days</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">HR note</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($requests as $r)
                            <tr>
                                <td class="px-4 py-3">{{ $r->start_date->format('d M') }} to {{ $r->end_date->format('d M Y') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($r->type) }}</td>
                                <td class="px-4 py-3">{{ $r->days() }}</td>
                                <td class="px-4 py-3"><x-badge :color="$colors[$r->status]">{{ ucfirst($r->status) }}</x-badge></td>
                                <td class="px-4 py-3 text-gray-500">{{ $r->review_note ?: 'N/A' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (in_array($r->status, ['pending', 'approved']) && ! $r->end_date->isPast())
                                        <form method="POST" action="{{ route('leave.cancel', $r) }}" onsubmit="return confirm('Cancel this request?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-xs text-rose-600 hover:underline">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No leave requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
