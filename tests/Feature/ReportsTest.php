<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    public function test_finance_report_is_for_admin_and_finance_only(): void
    {
        $this->actingAs($this->user('hr'))->get('/reports/finance')->assertForbidden();
        $this->actingAs($this->user('freelancer'))->get('/reports/finance')->assertForbidden();
        $this->actingAs($this->user('finance'))->get('/reports/finance')->assertOk();
        $this->actingAs($this->user('super-admin'))->get('/reports/finance')->assertOk();
    }

    public function test_hr_report_is_for_admin_and_hr_only(): void
    {
        $this->actingAs($this->user('finance'))->get('/hr/reports')->assertForbidden();
        $this->actingAs($this->user('hr'))->get('/hr/reports')->assertOk();
    }

    public function test_revenue_counts_only_paid_invoices_and_excludes_tax(): void
    {
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active']);
        $base = ['project_id' => $project->id, 'client_id' => $client->id, 'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(14)->toDateString(), 'tax_percent' => 10];

        Invoice::create($base + ['number' => 'T-1', 'status' => 'paid', 'paid_at' => now(), 'subtotal' => 100, 'tax_amount' => 10, 'total' => 110]);
        Invoice::create($base + ['number' => 'T-2', 'status' => 'sent', 'subtotal' => 50, 'tax_amount' => 5, 'total' => 55]);

        $r = app(\App\Services\FinanceReport::class)->build(now()->startOfYear(), now());

        $this->assertEquals(100.00, (float) $r['revenue']);
        $this->assertEquals(55.00, (float) $r['outstanding']);
    }

    public function test_csv_export_downloads(): void
    {
        $response = $this->actingAs($this->user('finance'))->get('/reports/finance/export?format=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
