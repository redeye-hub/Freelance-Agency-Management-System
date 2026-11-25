<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Project $project, Task $task)
    {
        abort_unless($project->isAccessibleBy($request->user()), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $task->comments()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Comment added.');
    }
}
