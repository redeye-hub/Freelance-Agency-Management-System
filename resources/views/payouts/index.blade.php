<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Freelancer Payouts</h2>
    </x-slot>

    @php $colors = ['pending' => 'amber', 'approved' => 'sky', 'paid' => 'emerald', 'rejected' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />
            <x-input-error :messages="$errors->get('payout')" />

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Waiting for approval</p><p class="text-2xl font-bold text-amber-600">${{ number_format($pendingNet, 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Approved, not yet paid</p><p class="text-2xl font-bold text-sky-600">${{ number_format($approvedNet, 2) }}</p></div>
                <div class="bg-white rounded-2xl shadow-sm p-5"><p class="text-sm text-gray-500">Paid out in total</p><p class="text-2xl font-bold text-emerald-600">${{ number_format($paidNet, 2) }}</p></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Approved hours with no payout yet</h3>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    @forelse ($unpaid as $r)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <span>{{ $r['user']->name }} <span class="text-gray-400">owed ${{ number_format($r['net'], 2) }} after commission</span></span>
                            <form method="POST" action="{{ route('payouts.store') }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $r['user']->id }}">
                                <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs hover:bg-indigo-700">Create payout</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-gray-400">Everything approved has already been put into a payout.</li>
                    @endforelse
                </ul>
            </div>

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex gap-3">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('payouts.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr><th class="px-4 py-3">Number</th><th class="px-4 py-3">Freelancer</th><th class="px-4 py-3">Hours</th><th class="px-4 py-3">Gross</th><th class="px-4 py-3">Commission</th><th class="px-4 py-3">Net</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($payouts as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">{{ $p->number }}</td>
                                <td class="px-4 py-3">{{ $p->user->name }}</td>
                                <td class="px-4 py-3">{{ number_format((float) $p->hours, 2) }}</td>
                                <td class="px-4 py-3">${{ number_format((float) $p->gross, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500">${{ number_format((float) $p->commission, 2) }}</td>
                                <td class="px-4 py-3 font-semibold">${{ number_format((float) $p->net, 2) }}</td>
                                <td class="px-4 py-3"><x-badge :color="$colors[$p->status]">{{ ucfirst($p->status) }}</x-badge></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('payouts.show', $p) }}" class="text-indigo-600 hover:underline">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">No payouts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payouts->links() }}
        </div>
    </div>
</x-app-layout>
