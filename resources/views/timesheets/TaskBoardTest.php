<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['super-admin', 'project-manager', 'finance', 'hr', 'freelancer', 'client'] as $r) {
            Role::findOrCreate($r);
        }
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function projectWith(User ...$members): Project
    {
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active']);
        foreach ($members as $m) {
            $project->assignments()->create(['user_id' => $m->id, 'hourly_rate' => 20, 'commission_percent' => 10, 'weekly_hours' => 20, 'status' => 'active']);
        }

        return $project;
    }

    public function test_board_access_follows_project_membership(): void
    {
        $member = $this->user('freelancer');
        $outsider = $this->user('freelancer');
        $project = $this->projectWith($member);

        $this->actingAs($member)->get("/projects/{$project->id}/board")->assertOk();
        $this->actingAs($outsider)->get("/projects/{$project->id}/board")->assertForbidden();
        $this->actingAs($this->user('project-manager'))->get("/projects/{$project->id}/board")->assertOk();
    }

    public function test_only_managers_create_tasks_and_assignee_must_be_on_the_project(): void
    {
        $pm = $this->user('project-manager');
        $member = $this->user('freelancer');
        $outsider = $this->user('freelancer');
        $project = $this->projectWith($member);

        $this->actingAs($member)->post("/projects/{$project->id}/tasks", ['title' => 'X', 'priority' => 'low'])->assertForbidden();

        $this->actingAs($pm)->post("/projects/{$project->id}/tasks", ['title' => 'Logo', 'priority' => 'high', 'assignee_id' => $outsider->id])
            ->assertSessionHasErrors('assignee_id');
        $this->assertSame(0, Task::count());

        $this->actingAs($pm)->post("/projects/{$project->id}/tasks", ['title' => 'Logo', 'priority' => 'high', 'assignee_id' => $member->id])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['title' => 'Logo', 'status' => 'todo', 'assignee_id' => $member->id]);
    }

    public function test_freelancer_moves_only_their_own_tasks(): void
    {
        $a = $this->user('freelancer');
        $b = $this->user('freelancer');
        $project = $this->projectWith($a, $b);
        $mine = Task::create(['project_id' => $project->id, 'title' => 'Mine', 'assignee_id' => $a->id]);
        $theirs = Task::create(['project_id' => $project->id, 'title' => 'Theirs', 'assignee_id' => $b->id]);

        $this->actingAs($a)->patch("/projects/{$project->id}/tasks/{$mine->id}/status", ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $mine->fresh()->status);

        $this->actingAs($a)->patch("/projects/{$project->id}/tasks/{$theirs->id}/status", ['status' => 'done'])->assertForbidden();
        $this->assertSame('todo', $theirs->fresh()->status);
    }

    public function test_comments_and_private_attachments(): void
    {
        Storage::fake('local');
        $member = $this->user('freelancer');
        $outsider = $this->user('freelancer');
        $project = $this->projectWith($member);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Brief']);

        $this->actingAs($member)->post("/projects/{$project->id}/tasks/{$task->id}/comments", ['body' => 'On it'])->assertSessionHasNoErrors();
        $this->assertSame(1, $task->comments()->count());
        $this->actingAs($outsider)->post("/projects/{$project->id}/tasks/{$task->id}/comments", ['body' => 'Hi'])->assertForbidden();

        $this->actingAs($member)->post("/projects/{$project->id}/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $file = $task->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($member)->get("/projects/{$project->id}/tasks/{$task->id}/attachments/{$file->id}")->assertOk();
        $this->actingAs($outsider)->get("/projects/{$project->id}/tasks/{$task->id}/attachments/{$file->id}")->assertForbidden();

        $this->actingAs($member)->post("/projects/{$project->id}/tasks/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('bad.exe', 10),
        ])->assertSessionHasErrors('file');
    }
}
