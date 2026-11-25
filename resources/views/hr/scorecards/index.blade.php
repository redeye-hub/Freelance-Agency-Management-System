<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Performance Scorecards</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">Freelancer</th>
                            <th class="px-4 py-3">Reviews</th>
                            <th class="px-4 py-3">Quality</th>
                            <th class="px-4 py-3">Communication</th>
                            <th class="px-4 py-3">Timeliness</th>
                            <th class="px-4 py-3">Overall</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($rows as $r)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $r['user']->name }}</td>
                                <td class="px-4 py-3">{{ $r['reviews'] }}</td>
                                <td class="px-4 py-3">{{ $r['quality'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $r['communication'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $r['timeliness'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 font-semibold">
                                    @if ($r['overall'] !== null)
                                        <x-badge :color="$r['overall'] >= 4 ? 'emerald' : ($r['overall'] >= 3 ? 'amber' : 'rose')">{{ $r['overall'] }} / 5</x-badge>
                                    @else
                                        <span class="text-gray-400 font-normal">Not rated</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('hr.scorecards.show', $r['user']) }}" class="text-indigo-600 hover:underline">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No freelancers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
