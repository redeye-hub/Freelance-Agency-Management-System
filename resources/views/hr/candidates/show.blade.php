<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Candidate: {{ $candidate->name }}</h2>
            <a href="{{ route('hr.candidates.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to pipeline</a>
        </div>
    </x-slot>

    @php
        $badgeColors = ['applied' => 'sky', 'screening' => 'indigo', 'interview' => 'amber', 'approved' => 'emerald', 'rejected' => 'rose'];
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    {{-- details --}}
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">Application</h3>
                            <x-badge :color="$badgeColors[$candidate->stage]">{{ ucfirst($candidate->stage) }}</x-badge>
                        </div>
                        <dl class="mt-4 grid sm:grid-cols-2 gap-4 text-sm">
                            <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-800">{{ $candidate->email }}</dd></div>
                            <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-800">{{ $candidate->phone ?: '—' }}</dd></div>
                            <div><dt class="text-gray-500">Headline</dt><dd class="font-medium text-gray-800">{{ $candidate->headline ?: '—' }}</dd></div>
                            <div><dt class="text-gray-500">Expected rate</dt><dd class="font-medium text-gray-800">{{ $candidate->expected_rate ? '$' . $candidate->expected_rate . '/hr' : '—' }}</dd></div>
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">Skills</dt>
                                <dd class="mt-1 flex flex-wrap gap-1">
                                    @forelse ($candidate->skillList() as $skill)
                                        <x-badge color="violet">{{ $skill }}</x-badge>
                                    @empty
                                        <span class="font-medium text-gray-800">—</span>
                                    @endforelse
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">Portfolio</dt>
                                <dd class="font-medium text-gray-800">
                                    @if ($candidate->portfolio_url)
                                        <a href="{{ $candidate->portfolio_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">{{ $candidate->portfolio_url }}</a>
                                    @else — @endif
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">Cover note</p>
                            <p class="mt-1 text-sm text-gray-800 whitespace-pre-line bg-sky-50 rounded-xl p-4">{{ $candidate->cover_note ?: '—' }}</p>
                        </div>
                    </div>

                    {{-- documents --}}
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800">Documents</h3>
                        <ul class="mt-3 divide-y divide-gray-100 text-sm">
                            @forelse ($candidate->documents as $doc)
                                <li class="py-2 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <x-badge color="teal">{{ strtoupper($doc->type) }}</x-badge>
                                        <a href="{{ route('hr.candidates.documents.download', [$candidate, $doc]) }}" class="ml-2 text-indigo-600 hover:underline break-all">{{ $doc->original_name }}</a>
                                        <p class="text-xs text-gray-400">{{ $doc->created_at->format('d M Y') }} · {{ $doc->uploader?->name }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('hr.candidates.documents.destroy', [$candidate, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </li>
                            @empty
                                <li class="py-3 text-gray-400">No documents yet.</li>
                            @endforelse
                        </ul>

                        <form method="POST" action="{{ route('hr.candidates.documents.store', $candidate) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap gap-2 items-center">
                            @csrf
                            <select name="type" class="rounded-lg border-gray-300 text-sm">
                                @foreach ($docTypes as $t)
                                    <option value="{{ $t }}">{{ strtoupper($t) }}</option>
                                @endforeach
                            </select>
                            <input type="file" name="file" required class="text-sm">
                            <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Upload</button>
                        </form>
                        <x-input-error class="mt-1" :messages="$errors->get('file')" />
                        <p class="mt-2 text-xs text-gray-400">PDF, JPG, PNG, DOC or DOCX, up to 5 MB. Files are private to HR and admin.</p>
                    </div>
                </div>

                {{-- actions --}}
                <div class="space-y-4">
                    @if ($candidate->stage !== 'approved')
                        <div class="bg-white rounded-2xl shadow-sm p-5">
                            <h3 class="font-semibold text-gray-800 mb-3">Pipeline stage</h3>
                            <form method="POST" action="{{ route('hr.candidates.stage', $candidate) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="stage" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    @foreach ($stages as $s)
                                        <option value="{{ $s }}" @selected($candidate->stage === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Move</button>
                            </form>
                        </div>
                    @endif

                    <div class="bg-white rounded-2xl shadow-sm p-5">
                        <h3 class="font-semibold text-gray-800 mb-3">Interview &amp; notes</h3>
                        <form method="POST" action="{{ route('hr.candidates.update', $candidate) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <select name="assigned_to" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Unassigned</option>
                                @foreach ($hrStaff as $m)
                                    <option value="{{ $m->id }}" @selected($candidate->assigned_to === $m->id)>{{ $m->name }}</option>
                                @endforeach
                            </select>
                            <input type="datetime-local" name="interview_at"
                                   value="{{ old('interview_at', $candidate->interview_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm">
                            <textarea name="notes" rows="4" placeholder="Internal notes"
                                      class="w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $candidate->notes) }}</textarea>
                            <button class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700">Save</button>
                        </form>
                    </div>

                    <div class="rounded-2xl p-5 {{ $candidate->user_id ? 'bg-emerald-50 border border-emerald-200' : 'bg-white shadow-sm' }}">
                        <h3 class="font-semibold text-gray-800 mb-2">Approval</h3>
                        @if ($candidate->user_id)
                            <p class="text-sm text-emerald-700">Approved. A freelancer account exists for this candidate.</p>
                        @elseif ($candidate->stage === 'interview')
                            <form method="POST" action="{{ route('hr.candidates.approve', $candidate) }}"
                                  onsubmit="return confirm('Create the freelancer account and send an invitation?')">
                                @csrf
                                <button class="w-full px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Approve &amp; send invite</button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500">Move this candidate to <strong>Interview</strong> to enable approval.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
