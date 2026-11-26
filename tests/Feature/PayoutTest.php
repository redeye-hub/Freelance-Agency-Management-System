<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FreelancerProfile;
use App\Models\Payout;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\EarningsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayoutTest extends TestCase
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

    /** 10 approved hours at rate 20, commission 10% => gross 200, commission 20, net 180. */
    private function freelancerWithApprovedHours(): User
    {
        $f = $this->user('freelancer');
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);
        $p = Project::create(['client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active']);
        $p->assignments()->create(['user_id' => $f->id, 'hourly_rate' => 20, 'commission_percent' => 10, 'weekly_hours' => 20, 'status' => 'active']);

        $ts = Timesheet::create(['user_id' => $f->id, 'week_start' => now()->startOfWeek()->toDateString(), 'status' => 'approved']);
        $ts->entries()->create(['project_id' => $p->id, 'work_date' => now()->startOfWeek()->toDateString(), 'hours' => 10]);

        return $f;
    }

    public function test_net_pay_is_gross_minus_commission(): void
    {
        $f = $this->freelancerWithApprovedHours();
        $payout = app(EarningsCalculator::class)->createPayout($f, $this->user('finance'));

        $this->assertEquals(200.00, (float) $payout->gross);
        $this->assertEquals(20.00, (float) $payout->commission);
        $this->assertEquals(180.00, (float) $payout->net);
    }

    public function test_hours_are_paid_only_once(): void
    {
        $f = $this->freelancerWithApprovedHours();
        $finance = $this->user('finance');
        $calc = app(EarningsCalculator::class);

        $this->assertNotNull($calc->createPayout($f, $finance));
        $this->assertNull($calc->createPayout($f, $finance)); // nothing left
        $this->assertSame(1, Payout::count());
    }

    public function test_rejecting_a_payout_releases_the_hours(): void
    {
        $f = $this->freelancerWithApprovedHours();
        $finance = $this->user('finance');
        $payout = app(EarningsCalculator::class)->createPayout($f, $finance);

        $this->actingAs($finance)->patch("/payouts/{$payout->id}/reject", ['note' => 'Wrong amount'])->assertSessionHasNoErrors();

        $this->assertSame('rejected', $payout->fresh()->status);
        $this->assertNotNull(app(EarningsCalculator::class)->createPayout($f, $finance)); // payable again
    }

    public function test_freelancer_cannot_approve_payouts(): void
    {
        $f = $this->freelancerWithApprovedHours();
        $payout = app(EarningsCalculator::class)->createPayout($f, $this->user('finance'));

        $this->actingAs($f)->patch("/payouts/{$payout->id}/approve")->assertForbidden();
        $this->assertSame('pending', $payout->fresh()->status);
    }
}
