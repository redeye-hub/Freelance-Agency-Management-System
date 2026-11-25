<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\FreelancerProfile;
use App\Models\HrAuditLog;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\InviteUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $byStage = Candidate::with('assignee')
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('skills', 'like', "%{$s}%")
            ))
            ->latest()
            ->get()
            ->groupBy('stage');

        return view('hr.candidates.index', ['byStage' => $byStage, 'stages' => Candidate::STAGES]);
    }

    public function show(Candidate $candidate)
    {
        $candidate->load('documents.uploader', 'assignee', 'user');

        return view('hr.candidates.show', [
            'candidate' => $candidate,
            'stages' => array_values(array_diff(Candidate::STAGES, ['approved'])),
            'hrStaff' => User::role(['super-admin', 'hr'])->where('status', 'active')->orderBy('name')->get(),
            'docTypes' => CandidateDocument::TYPES,
        ]);
    }

    public function update(Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'interview_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $candidate->update($data);
        HrAuditLog::record('candidate.updated', $candidate);

        return back()->with('success', 'Candidate updated.');
    }

    public function updateStage(Request $request, Candidate $candidate)
    {
        // "approved" is only reachable through approve(), which creates the account.
        $data = $request->validate([
            'stage' => ['required', Rule::in(array_diff(Candidate::STAGES, ['approved']))],
        ]);

        if ($candidate->stage === 'approved') {
            return back()->withErrors(['stage' => 'An approved candidate already has an account.']);
        }

        $from = $candidate->stage;
        $candidate->update(['stage' => $data['stage']]);
        HrAuditLog::record('candidate.stage', $candidate, ['from' => $from, 'to' => $data['stage']]);

        return back()->with('success', "{$candidate->name} moved to {$data['stage']}.");
    }

    /** Approve = create the freelancer account + profile and send the invite email. */
    public function approve(Candidate $candidate)
    {
        if ($candidate->user_id) {
            return back()->withErrors(['stage' => 'This candidate was already approved.']);
        }

        if ($candidate->stage !== 'interview') {
            return back()->withErrors(['stage' => 'Only candidates in the "interview" stage can be approved.']);
        }

        if (User::where('email', $candidate->email)->exists()) {
            return back()->withErrors(['stage' => 'A user with this email already exists.']);
        }

        $user = DB::transaction(function () use ($candidate) {
            $user = User::create([
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('freelancer');

            $profile = FreelancerProfile::create([
                'user_id' => $user->id,
                'headline' => $candidate->headline,
                'bio' => $candidate->cover_note,
                'hourly_rate' => $candidate->expected_rate,
                'availability' => 'available',
            ]);
            $profile->skills()->sync(
                collect($candidate->skillList())
                    ->map(fn ($s) => Skill::firstOrCreate(['name' => $s])->id)
                    ->all()
            );

            $candidate->update(['stage' => 'approved', 'user_id' => $user->id]);

            return $user;
        });

        $user->notify(new InviteUserNotification(Password::createToken($user)));
        HrAuditLog::record('candidate.approved', $candidate, ['user_id' => $user->id]);

        return back()->with('success', "{$candidate->name} approved. An invitation was sent to {$user->email}.");
    }

    public function storeDocument(Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(CandidateDocument::TYPES)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $file = $data['file'];
        // Private disk: not reachable by URL, only through downloadDocument().
        $path = $file->store("candidates/{$candidate->id}", 'local');

        $doc = $candidate->documents()->create([
            'type' => $data['type'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);
        HrAuditLog::record('document.uploaded', $doc, ['candidate_id' => $candidate->id, 'type' => $data['type']]);

        return back()->with('success', 'Document uploaded.');
    }

    public function downloadDocument(Candidate $candidate, CandidateDocument $document)
    {
        abort_unless($document->candidate_id === $candidate->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        HrAuditLog::record('document.downloaded', $document, ['candidate_id' => $candidate->id]);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroyDocument(Candidate $candidate, CandidateDocument $document)
    {
        abort_unless($document->candidate_id === $candidate->id, 404);

        Storage::disk('local')->delete($document->path);
        HrAuditLog::record('document.deleted', $document, ['candidate_id' => $candidate->id]);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
