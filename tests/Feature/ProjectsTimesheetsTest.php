<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectsTimesheetsTest extends TestCase
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

    private function project(array $attrs = []): Project
    {
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);

        return Project::create($attrs + [
            'client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active',
        ]);
    }

    public function test_only_managers_can_open_projects(): void
    {
        $this->actingAs($this->user('freelancer'))->get('/projects')->assertForbidden();
        $this->actingAs($this->user('project-manager'))->get('/projects')->assertOk();
    }

    public function test_milestones_cannot_exceed_budget_and_assignment_rules_hold(): void
    {
        $pm = $this->user('project-manager');
        $freelancer = $this->user('freelancer');
        $project = $this->project();

        $this->actingAs($pm)->post("/projects/{$project->id}/milestones", ['title' => 'Design', 'amount' => 600])
            ->assertSessionHasNoErrors();
        $this->actingAs($pm)->post("/projects/{$project->id}/milestones", ['title' => 'Build', 'amount' => 500])
            ->assertSessionHasErrors('amount');
        $this->assertSame(1, $project->milestones()->count());

        $payload = ['user_id' => $freelancer->id, 'hourly_rate' => 20, 'commission_percent' => 10, 'weekly_hours' => 20];
        $this->actingAs($pm)->post("/projects/{$project->id}/assignments", $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id, 'user_id' => $freelancer->id, 'commission_percent' => 10,
        ]);

        // same freelancer twice is rejected, and a non-freelancer cannot be assigned
        $this->actingAs($pm)->post("/projects/{$project->id}/assignments", $payload)->assertSessionHasErrors('user_id');
        $this->actingAs($pm)->post("/projects/{$project->id}/assignments", ['user_id' => $pm->id] + $payload)
            ->assertSessionHasErrors('user_id');
    }

    public function test_timesheet_flow_log_submit_lock_approve(): void
    {
        $pm = $this->user('project-manager');
        $freelancer = $this->user('freelancer');
        $project = $this->project();
        $other = $this->project(['name' => 'Not mine']);
        $project->assignments()->create(['user_id' => $freelancer->id, 'hourly_rate' => 20, 'commission_percent' => 10, 'weekly_hours' => 20, 'status' => 'active']);

        $today = now()->toDateString();

        // cannot log on a project you are not assigned to
        $this->actingAs($freelancer)->post('/my/timesheet/entries', ['project_id' => $other->id, 'work_date' => $today, 'hours' => 2])
            ->assertSessionHasErrors('project_id');

        $this->actingAs($freelancer)->post('/my/timesheet/entries', ['project_id' => $project->id, 'work_date' => $today, 'hours' => 4, 'description' => 'Header'])
            ->assertSessionHasNoErrors();
        $sheet = Timesheet::firstOrFail();
        $this->assertSame(1, $sheet->entries()->count());

        $this->actingAs($freelancer)->post('/my/timesheet/submit', ['week' => $today]);
        $this->assertSame('submitted', $sheet->fresh()->status);

        // locked after submit
        $this->actingAs($freelancer)->post('/my/timesheet/entries', ['project_id' => $project->id, 'work_date' => $today, 'hours' => 1])
            ->assertSessionHasErrors('hours');

        // a freelancer cannot approve their own sheet, a manager can
        $this->actingAs($freelancer)->patch("/timesheets/{$sheet->id}/approve")->assertForbidden();
        $this->actingAs($pm)->patch("/timesheets/{$sheet->id}/approve");
        $this->assertSame('approved', $sheet->fresh()->status);
    }

    public function test_rejected_timesheet_becomes_editable_again(): void
    {
        $pm = $this->user('project-manager');
        $freelancer = $this->user('freelancer');
        $project = $this->project();
        $project->assignments()->create(['user_id' => $freelancer->id, 'hourly_rate' => 20, 'commission_percent' => 10, 'weekly_hours' => 20, 'status' => 'active']);
        $today = now()->toDateString();

        $this->actingAs($freelancer)->post('/my/timesheet/entries', ['project_id' => $project->id, 'work_date' => $today, 'hours' => 3]);
        $this->actingAs($freelancer)->post('/my/timesheet/submit', ['week' => $today]);
        $sheet = Timesheet::firstOrFail();

        $this->actingAs($pm)->patch("/timesheets/{$sheet->id}/reject", [])->assertSessionHasErrors('review_note');
        $this->actingAs($pm)->patch("/timesheets/{$sheet->id}/reject", ['review_note' => 'Too vague']);
        $this->assertSame('rejected', $sheet->fresh()->status);

        $this->actingAs($freelancer)->post('/my/timesheet/entries', ['project_id' => $project->id, 'work_date' => $today, 'hours' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $sheet->entries()->count());
    }
}
