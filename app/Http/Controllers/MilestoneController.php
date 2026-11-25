<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'due_date' => ['nullable', 'date'],
        ]);

        // Milestone amounts must never add up to more than the project budget.
        $allocated = (float) $project->milestones()->sum('amount');
        if ($allocated + (float) $data['amount'] > (float) $project->budget) {
            return back()->withInput()->withErrors([
                'amount' => 'Milestones would exceed the project budget of ' . number_format((float) $project->budget, 2) . '.',
            ]);
        }

        $project->milestones()->create($data + ['status' => 'pending']);

        return back()->with('success', 'Milestone added.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Milestone::STATUSES)],
        ]);

        $milestone->update($data);

        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();

        return back()->with('success', 'Milestone removed.');
    }
}
