<?php

namespace App\Http\Controllers;

use App\Models\HrAuditLog;
use App\Models\PerformanceReview;
use App\Models\Project;
use Illuminate\Http\Request;

/** Manager side: rate every freelancer on a completed project. */
class ProjectReviewController extends Controller
{
    public function edit(Project $project)
    {
        if ($project->status !== 'completed') {
            return redirect()->route('projects.show', $project)
                ->withErrors(['status' => 'You can rate freelancers once the project is completed.']);
        }

        return view('projects.reviews', [
            'project' => $project,
            'assignments' => $project->assignments()->with('user')->get(),
            'reviews' => PerformanceReview::where('project_id', $project->id)->get()->keyBy('freelancer_id'),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        abort_unless($project->status === 'completed', 403);

        $assigned = $project->assignments()->pluck('user_id')->all();

        $request->validate([
            'reviews' => ['required', 'array'],
            'reviews.*.quality' => ['nullable', 'integer', 'between:1,5'],
            'reviews.*.communication' => ['nullable', 'integer', 'between:1,5'],
            'reviews.*.timeliness' => ['nullable', 'integer', 'between:1,5'],
            'reviews.*.comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $saved = 0;
        foreach ($request->input('reviews', []) as $freelancerId => $row) {
            $freelancerId = (int) $freelancerId;

            // only freelancers who really worked on this project, and only complete rows
            if (! in_array($freelancerId, $assigned, true)) {
                continue;
            }
            if (empty($row['quality']) || empty($row['communication']) || empty($row['timeliness'])) {
                continue;
            }

            $review = PerformanceReview::updateOrCreate(
                ['project_id' => $project->id, 'freelancer_id' => $freelancerId],
                [
                    'reviewer_id' => $request->user()->id,
                    'quality' => (int) $row['quality'],
                    'communication' => (int) $row['communication'],
                    'timeliness' => (int) $row['timeliness'],
                    'comment' => $row['comment'] ?? null,
                ]
            );
            HrAuditLog::record('review.saved', $review, ['project_id' => $project->id]);
            $saved++;
        }

        return redirect()->route('projects.show', $project)
            ->with('success', $saved ? "Saved {$saved} review(s)." : 'No complete ratings were entered.');
    }
}
