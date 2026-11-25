<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /** A freelancer is "full" at this many planned hours per week. */
    public const WEEKLY_CAPACITY = 40;

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'weekly_hours' => ['required', 'integer', 'min:1', 'max:80'],
        ]);

        $freelancer = User::findOrFail($data['user_id']);
        if (! $freelancer->hasRole('freelancer') || $freelancer->status !== 'active') {
            return back()->withErrors(['user_id' => 'Pick an active freelancer.']);
        }

        $existing = $project->assignments()->where('user_id', $freelancer->id)->first();
        if ($existing && $existing->status === 'active') {
            return back()->withErrors(['user_id' => "{$freelancer->name} is already on this project."]);
        }

        // Re-assigning someone whose assignment ended simply re-opens the same row (unique project+user).
        $assignment = $existing
            ? tap($existing)->update($data + ['status' => 'active'])
            : $project->assignments()->create($data + ['status' => 'active']);

        $message = "{$freelancer->name} assigned at {$assignment->hourly_rate}/hr, {$assignment->commission_percent}% commission.";

        $planned = $this->plannedHours($freelancer->id);
        if ($planned > self::WEEKLY_CAPACITY) {
            $message .= " Warning: {$freelancer->name} is now planned for {$planned} h/week (capacity " . self::WEEKLY_CAPACITY . ').';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Project $project, ProjectAssignment $assignment)
    {
        $data = $request->validate([
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'weekly_hours' => ['required', 'integer', 'min:1', 'max:80'],
            'status' => ['required', Rule::in(['active', 'ended'])],
        ]);

        $assignment->update($data);

        return back()->with('success', 'Assignment updated.');
    }

    private function plannedHours(int $userId): int
    {
        return (int) ProjectAssignment::where('user_id', $userId)
            ->where('status', 'active')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['planning', 'active']))
            ->sum('weekly_hours');
    }
}
