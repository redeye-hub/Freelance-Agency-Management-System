<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrModuleTest extends TestCase
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

    private function userWithRole(string $role, array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['status' => 'active']);
        $u->assignRole($role);

        return $u;
    }

    private function candidate(array $attrs = []): Candidate
    {
        return Candidate::create($attrs + [
            'name' => 'Nadia Karim',
            'email' => 'nadia@example.com',
            'headline' => 'Vue developer',
            'skills' => 'Vue, Tailwind',
            'expected_rate' => 20,
            'cover_note' => 'Hello',
            'stage' => 'applied',
        ]);
    }

    public function test_public_can_apply_and_duplicate_is_blocked(): void
    {
        $payload = ['name' => 'A B', 'email' => 'ab@example.com', 'cover_note' => 'Hi'];

        $this->post('/join', $payload)->assertRedirect(route('apply.create'));
        $this->assertDatabaseHas('candidates', ['email' => 'ab@example.com', 'stage' => 'applied']);

        $this->post('/join', $payload)->assertSessionHasErrors('email');
        $this->assertSame(1, Candidate::count());
    }

    public function test_only_hr_and_admin_can_open_pipeline(): void
    {
        $this->actingAs($this->userWithRole('freelancer'))->get('/hr/candidates')->assertForbidden();
        $this->actingAs($this->userWithRole('project-manager'))->get('/hr/candidates')->assertForbidden();
        $this->actingAs($this->userWithRole('hr'))->get('/hr/candidates')->assertOk();
        $this->actingAs($this->userWithRole('super-admin'))->get('/hr/candidates')->assertOk();
    }

    public function test_approval_requires_interview_stage_then_creates_freelancer_and_invites(): void
    {
        Notification::fake();
        $hr = $this->userWithRole('hr');
        $c = $this->candidate();

        $this->actingAs($hr)->post("/hr/candidates/{$c->id}/approve")->assertSessionHasErrors('stage');
        $this->assertNull($c->fresh()->user_id);

        $this->actingAs($hr)->patch("/hr/candidates/{$c->id}/stage", ['stage' => 'interview']);
        $this->actingAs($hr)->post("/hr/candidates/{$c->id}/approve")->assertSessionHas('success');

        $user = User::where('email', 'nadia@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('freelancer'));
        $this->assertSame('approved', $c->fresh()->stage);
        $this->assertSame($user->id, $c->fresh()->user_id);
        $this->assertEqualsCanonicalizing(['Vue', 'Tailwind'], $user->freelancerProfile->skills->pluck('name')->all());
        Notification::assertSentTo($user, \App\Notifications\InviteUserNotification::class);

        // second approval is rejected, stage cannot be forced to approved
        $this->actingAs($hr)->post("/hr/candidates/{$c->id}/approve")->assertSessionHasErrors('stage');
        $this->actingAs($hr)->patch("/hr/candidates/{$c->id}/stage", ['stage' => 'approved'])->assertSessionHasErrors('stage');
        $this->assertSame(1, User::where('email', 'nadia@example.com')->count());
    }

    public function test_documents_are_stored_privately_and_scoped_to_candidate(): void
    {
        Storage::fake('local');
        $hr = $this->userWithRole('hr');
        $c = $this->candidate();
        $other = $this->candidate(['email' => 'other@example.com']);

        $this->actingAs($hr)->post("/hr/candidates/{$c->id}/documents", [
            'type' => 'nda',
            'file' => UploadedFile::fake()->create('nda.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('success');

        $doc = $c->documents()->firstOrFail();
        Storage::disk('local')->assertExists($doc->path);

        $this->actingAs($hr)->get("/hr/candidates/{$c->id}/documents/{$doc->id}")->assertOk();
        $this->actingAs($hr)->get("/hr/candidates/{$other->id}/documents/{$doc->id}")->assertNotFound();

        $this->actingAs($hr)->post("/hr/candidates/{$c->id}/documents", [
            'type' => 'id',
            'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('file');

        $this->actingAs($this->userWithRole('freelancer'))
            ->get("/hr/candidates/{$c->id}/documents/{$doc->id}")->assertForbidden();
    }

    public function test_offboarding_only_affects_freelancers_and_needs_a_reason(): void
    {
        $hr = $this->userWithRole('hr');
        $freelancer = $this->userWithRole('freelancer');
        $manager = $this->userWithRole('project-manager');

        $this->actingAs($hr)->patch("/hr/offboarding/{$manager->id}", ['status' => 'suspended', 'reason' => 'x'])
            ->assertForbidden();
        $this->assertSame('active', $manager->fresh()->status);

        $this->actingAs($hr)->patch("/hr/offboarding/{$freelancer->id}", ['status' => 'suspended'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($hr)->patch("/hr/offboarding/{$freelancer->id}", ['status' => 'archived', 'reason' => 'Contract ended'])
            ->assertSessionHas('success');
        $fresh = $freelancer->fresh();
        $this->assertSame('archived', $fresh->status);
        $this->assertSame('Contract ended', $fresh->offboarding_reason);
        $this->assertNotNull($fresh->offboarded_at);
        $this->assertDatabaseHas('hr_audit_logs', ['action' => 'freelancer.status', 'subject_id' => $freelancer->id]);

        $this->actingAs($hr)->patch("/hr/offboarding/{$freelancer->id}", ['status' => 'active']);
        $this->assertNull($freelancer->fresh()->offboarding_reason);
    }
}
