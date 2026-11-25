<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Freelancer Contracts</h2>
    </x-slot>

    @php $colors = ['sent' => 'amber', 'accepted' => 'emerald', 'declined' => 'rose']; @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">Freelancer</th>
                            <th class="px-4 py-3">Project</th>
                            <th class="px-4 py-3">Rate / commission / hours</th>
                            <th class="px-4 py-3">Contract</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($assignments as $a)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $a->user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $a->project->name }}<br><span class="text-xs text-gray-400">{{ $a->project->client->company_name }}</span></td>
                                <td class="px-4 py-3 text-gray-600">${{ $a->hourly_rate }}/hr · {{ $a->commission_percent }}% · {{ $a->weekly_hours }} h/week</td>
                                <td class="px-4 py-3">
                                    @if ($a->contract)
                                        <x-badge :color="$colors[$a->contract->status]">{{ ucfirst($a->contract->status) }}</x-badge>
                                        <p class="mt-1 text-xs text-gray-500">{{ $a->contract->number }}</p>
                                        @if ($a->contract->status === 'declined' && $a->contract->response_note)
                                            <p class="mt-1 text-xs text-rose-600">{{ $a->contract->response_note }}</p>
                                        @endif
                                    @else
                                        <span class="text-gray-400">None yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($a->contract)
                                        <a href="{{ route('hr.contracts.pdf', $a->contract) }}" class="text-indigo-600 hover:underline mr-3">PDF</a>
                                        @if ($a->contract->status !== 'accepted')
                                            <form method="POST" action="{{ route('hr.contracts.withdraw', $a->contract) }}" class="inline" onsubmit="return confirm('Withdraw this contract?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-rose-600 hover:underline">Withdraw</button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('hr.contracts.store') }}" class="space-y-1">
                                            @csrf
                                            <input type="hidden" name="project_assignment_id" value="{{ $a->id }}">
                                            <input type="date" name="end_date" class="rounded-lg border-gray-300 text-xs" title="End date (optional, defaults to project deadline)">
                                            <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs hover:bg-indigo-700">Generate and send</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No active assignments yet. A project manager must assign freelancers to projects first.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $assignments->links() }}
            <x-input-error :messages="$errors->get('project_assignment_id')" />
        </div>
    </div>
</x-app-layout>
