<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with('client', 'manager')
            ->withCount('activeAssignments')
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', ['projects' => $projects, 'statuses' => Project::STATUSES]);
    }

    public function create()
    {
        return view('projects.create', $this->formData(new Project(['status' => 'planning'])));
    }

    public function store(Request $request)
    {
        $project = Project::create($this->validated($request));

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created. Add milestones and assign freelancers below.');
    }

    public function show(Project $project)
    {
        $project->load('client', 'manager', 'milestones', 'assignments.user');

        $assignedIds = $project->assignments->where('status', 'active')->pluck('user_id');

        return view('projects.show', [
            'project' => $project,
            'statuses' => Project::STATUSES,
            'freelancers' => User::role('freelancer')->where('status', 'active')
                ->with('freelancerProfile')
                ->whereNotIn('id', $assignedIds)
                ->orderBy('name')->get(),
            'milestoneTotal' => (float) $project->milestones->sum('amount'),
        ]);
    }

    public function edit(Project $project)
    {
        return view('projects.edit', $this->formData($project));
    }

    public function update(Request $request, Project $project)
    {
        $project->update($this->validated($request));

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'clients' => Client::where('status', 'active')->orderBy('company_name')->get(),
            'managers' => User::role(['super-admin', 'project-manager'])
                ->where('status', 'active')->orderBy('name')->get(),
            'statuses' => Project::STATUSES,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'budget' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Project::STATUSES)],
        ]);
    }
}
