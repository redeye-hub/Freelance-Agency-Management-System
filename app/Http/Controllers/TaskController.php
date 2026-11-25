<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless($project->isManagedBy($request->user()), 403);

        $task = $project->tasks()->create($this->validated($request, $project) + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('projects.board', $project)->with('success', "Task \"{$task->title}\" created.");
    }

    public function show(Request $request, Project $project, Task $task)
    {
        abort_unless($project->isAccessibleBy($request->user()), 403);

        $task->load('assignee', 'creator', 'milestone', 'comments.user', 'attachments.user');

        return view('tasks.show', [
            'project' => $project,
            'task' => $task,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'team' => \App\Models\User::whereIn('id', $project->activeAssignments()->pluck('user_id'))->orderBy('name')->get(),
            'milestones' => $project->milestones()->orderBy('due_date')->get(),
            'canManage' => $project->isManagedBy($request->user()),
            'canMove' => $project->isManagedBy($request->user()) || $task->assignee_id === $request->user()->id,
        ]);
    }

    public function update(Request $request, Project $project, Task $task)
    {
        abort_unless($project->isManagedBy($request->user()), 403);

        $task->update($this->validated($request, $project));

        return back()->with('success', 'Task updated.');
    }

    /** Managers can move any card; a freelancer can only move cards assigned to them. */
    public function updateStatus(Request $request, Project $project, Task $task)
    {
        $user = $request->user();
        abort_unless($project->isAccessibleBy($user), 403);
        abort_unless($project->isManagedBy($user) || $task->assignee_id === $user->id, 403, 'You can only move your own tasks.');

        $data = $request->validate(['status' => ['required', Rule::in(Task::STATUSES)]]);
        $task->update($data);

        return back()->with('success', "\"{$task->title}\" moved to " . str_replace('_', ' ', $data['status']) . '.');
    }

    public function destroy(Request $request, Project $project, Task $task)
    {
        abort_unless($project->isManagedBy($request->user()), 403);

        foreach ($task->attachments as $file) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($file->path);
        }
        $task->delete();

        return redirect()->route('projects.board', $project)->with('success', 'Task deleted.');
    }

    private function validated(Request $request, Project $project): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            // the assignee must be an active member of THIS project
            'assignee_id' => [
                'nullable',
                Rule::exists('project_assignments', 'user_id')
                    ->where('project_id', $project->id)->where('status', 'active'),
            ],
            // the milestone must belong to THIS project
            'milestone_id' => [
                'nullable',
                Rule::exists('milestones', 'id')->where('project_id', $project->id),
            ],
        ]);
    }
}
