<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Project $project, Task $task)
    {
        abort_unless($project->isAccessibleBy($request->user()), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
        ]);

        $file = $data['file'];
        // Private disk: never reachable by URL, only through download().
        $path = $file->store("tasks/{$task->id}", 'local');

        $task->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'File uploaded.');
    }

    public function download(Request $request, Project $project, Task $task, TaskAttachment $attachment)
    {
        abort_unless($project->isAccessibleBy($request->user()), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /** Only the uploader or a manager can delete a file. */
    public function destroy(Request $request, Project $project, Task $task, TaskAttachment $attachment)
    {
        $user = $request->user();
        abort_unless($project->isManagedBy($user) || $attachment->user_id === $user->id, 403);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'File deleted.');
    }
}
