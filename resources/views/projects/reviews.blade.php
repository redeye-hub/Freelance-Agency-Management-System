<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rate freelancers: {{ $project->name }}</h2>
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to project</a>
        </div>
    </x-slot>

    @php
        $fields = ['quality' => 'Quality of work', 'communication' => 'Communication', 'timeliness' => 'Timeliness'];
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <p class="text-sm text-gray-500">Rate each freelancer from 1 (poor) to 5 (excellent). Rows left blank are skipped. HR keeps these as the freelancer's scorecard.</p>

            <form method="POST" action="{{ route('projects.reviews.update', $project) }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach ($assignments as $a)
                    @php $r = $reviews->get($a->user_id); @endphp
                    <div class="bg-white rounded-2xl shadow-sm p-5">
                        <p class="font-semibold text-gray-800">{{ $a->user->name }}
                            @if ($r) <x-badge color="emerald">Rated {{ $r->overall() }}/5</x-badge> @endif
                        </p>
                        <div class="mt-3 grid sm:grid-cols-3 gap-3">
                            @foreach ($fields as $key => $label)
                                <label class="text-xs text-gray-500">{{ $label }}
                                    <select name="reviews[{{ $a->user_id }}][{{ $key }}]" class="mt-0.5 w-full rounded-lg border-gray-300 text-sm">
                                        <option value="">Skip</option>
                                        @foreach ([5, 4, 3, 2, 1] as $n)
                                            <option value="{{ $n }}" @selected((int) old("reviews.{$a->user_id}.{$key}", $r?->{$key}) === $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                        <textarea name="reviews[{{ $a->user_id }}][comment]" rows="2" placeholder="Comment (optional)" class="mt-3 w-full rounded-lg border-gray-300 text-sm">{{ old("reviews.{$a->user_id}.comment", $r?->comment) }}</textarea>
                    </div>
                @endforeach

                <button class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Save ratings</button>
            </form>
        </div>
    </div>
</x-app-layout>
