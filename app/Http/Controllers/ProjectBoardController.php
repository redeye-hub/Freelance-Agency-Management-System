<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectBoardController extends Controller
{
    /** Freelancer landing page: the projects they are working on. */
    public function mine(Request $request)
    {
        $projects = ProjectAssignment::with('project.client')
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->get()
            ->pluck('project')
            ->filter();

        return view('projects.mine', ['projects' => $projects]);
    }

    public function show(Request $request, Project $project)
    {
        $user = $request->user();
        abort_unless($project->isAccessibleBy($user), 403);

        $tasks = $project->tasks()
            ->with('assignee', 'milestone')
            ->withCount('comments', 'attachments')
            ->when($request->boolean('mine'), fn ($q) => $q->where('assignee_id', $user->id))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->groupBy('status');

        $team = User::whereIn('id', $project->activeAssignments()->pluck('user_id'))->orderBy('name')->get();

        return view('projects.board', [
            'project' => $project,
            'byStatus' => $tasks,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'team' => $team,
            'milestones' => $project->milestones()->orderBy('due_date')->get(),
            'canManage' => $project->isManagedBy($user),
        ]);
    }
}
