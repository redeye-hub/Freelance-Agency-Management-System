<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\FreelancerProfile;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrModule2Test extends TestCase
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
        if ($role === 'freelancer') {
            FreelancerProfile::create(['user_id' => $u->id, 'availability' => 'available', 'hourly_rate' => 20]);
        }

        return $u;
    }

    private function project(array $attrs = []): Project
    {
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);

        return Project::create($attrs + ['client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active']);
    }

    private function assign(Project $p, User $u, int $hours = 20): ProjectAssignment
    {
        return $p->assignments()->create(['user_id' => $u->id, 'hourly_rate' => 25, 'commission_percent' => 12.5, 'weekly_hours' => $hours, 'status' => 'active']);
    }

    public function test_leave_overlap_is_blocked_and_availability_follows_approved_leave(): void
    {
        $hr = $this->user('hr');
        $f = $this->user('freelancer');
        $dates = ['type' => 'annual', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString()];

        $this->actingAs($f)->post('/my/leave', $dates)->assertSessionHasNoErrors();
        $this->actingAs($f)->post('/my/leave', $dates)->assertSessionHasErrors('start_date'); // overlap
        $leave = LeaveRequest::firstOrFail();

        // a freelancer cannot approve their own leave
        $this->actingAs($f)->patch("/hr/leave/{$leave->id}/approve")->assertForbidden();

        $this->actingAs($hr)->patch("/hr/leave/{$leave->id}/approve");
        $profile = $f->freelancerProfile()->first();
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('unavailable', $profile->availability);
        $this->assertSame('available', $profile->availability_before_leave);

        // cancelling restores the old availability straight away
        $this->actingAs($f)->patch("/my/leave/{$leave->id}/cancel");
        $profile = $f->freelancerProfile()->first();
        $this->assertSame('available', $profile->availability);
        $this->assertNull($profile->availability_before_leave);
    }

    public function test_workload_flags_over_allocation(): void
    {
        $hr = $this->user('hr');
        $busy = $this->user('freelancer');
        $this->assign($this->project(), $busy, 30);
        $this->assign($this->project(['name' => 'App']), $busy, 20); // 50 h > 40 h capacity

        $this->actingAs($hr)->get('/hr/workload')->assertOk()->assertSee('Over-allocated');
        $this->actingAs($this->user('project-manager'))->get('/hr/workload')->assertForbidden();
    }

    public function test_contract_is_a_snapshot_and_only_the_owner_can_accept(): void
    {
        $hr = $this->user('hr');
        $f = $this->user('freelancer');
        $stranger = $this->user('freelancer');
        $assignment = $this->assign($this->project(), $f);

        $this->actingAs($hr)->post('/hr/contracts', ['project_assignment_id' => $assignment->id])->assertSessionHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertSame('sent', $contract->status);
        $this->assertNotNull($contract->number);

        // second contract for the same assignment is refused
        $this->actingAs($hr)->post('/hr/contracts', ['project_assignment_id' => $assignment->id])->assertSessionHasErrors('project_assignment_id');

        // later changes to the assignment do not touch the contract
        $assignment->update(['hourly_rate' => 99]);
        $this->assertEquals(25, $contract->fresh()->hourly_rate);

        $this->actingAs($stranger)->patch("/my/contracts/{$contract->id}/accept")->assertForbidden();
        $this->actingAs($f)->patch("/my/contracts/{$contract->id}/accept");
        $this->assertSame('accepted', $contract->fresh()->status);

        // accepted contracts cannot be withdrawn
        $this->actingAs($hr)->delete("/hr/contracts/{$contract->id}")->assertSessionHasErrors('status');
        $this->assertSame(1, Contract::count());
    }

    public function test_reviews_need_a_completed_project_and_feed_the_scorecard(): void
    {
        $pm = $this->user('project-manager');
        $hr = $this->user('hr');
        $f = $this->user('freelancer');
        $outsider = $this->user('freelancer');
        $project = $this->project(['status' => 'active']);
        $this->assign($project, $f);

        $row = ['quality' => 5, 'communication' => 4, 'timeliness' => 3, 'comment' => 'Solid'];

        $this->actingAs($pm)->put("/projects/{$project->id}/reviews", ['reviews' => [$f->id => $row]])->assertForbidden();

        $project->update(['status' => 'completed']);
        $this->actingAs($pm)->put("/projects/{$project->id}/reviews", ['reviews' => [$f->id => $row, $outsider->id => $row]])
            ->assertSessionHasNoErrors();

        // only the assigned freelancer was rated
        $this->assertSame(1, PerformanceReview::count());
        $this->assertDatabaseHas('performance_reviews', ['freelancer_id' => $f->id, 'quality' => 5]);

        $this->actingAs($hr)->get('/hr/scorecards')->assertOk()->assertSee($f->name);
        $this->actingAs($hr)->get("/hr/scorecards/{$f->id}")->assertOk()->assertSee('Solid');
    }
}
