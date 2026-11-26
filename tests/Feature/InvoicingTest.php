<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Services\SandboxGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoicingTest extends TestCase
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

    private function sentInvoice(): Invoice
    {
        $client = Client::create(['company_name' => 'Acme', 'contact_name' => 'Ann', 'email' => 'ann@acme.test']);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Website', 'budget' => 1000, 'status' => 'active']);

        return Invoice::create([
            'number' => 'INV-TEST-1', 'project_id' => $project->id, 'client_id' => $client->id,
            'status' => 'sent', 'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 100, 'tax_percent' => 10, 'tax_amount' => 10, 'total' => 110,
        ]);
    }

    public function test_freelancer_cannot_open_invoices(): void
    {
        $f = User::factory()->create();
        $f->assignRole('freelancer');

        $this->actingAs($f)->get('/invoices')->assertForbidden();
    }

    public function test_finance_can_open_invoice_list(): void
    {
        $u = User::factory()->create();
        $u->assignRole('finance');

        $this->actingAs($u)->get('/invoices')->assertOk();
    }

    public function test_public_page_works_by_token_and_a_declined_card_stays_unpaid(): void
    {
        $inv = $this->sentInvoice();

        $this->get("/pay/{$inv->public_token}")->assertOk();

        $this->post("/pay/{$inv->public_token}", ['card_number' => SandboxGateway::DECLINE_CARD, 'expiry' => '12/30', 'cvc' => '123'])
            ->assertRedirect();

        $this->assertSame('sent', $inv->fresh()->status);
        $this->assertDatabaseHas('payments', ['invoice_id' => $inv->id, 'status' => 'failed']);
    }

    public function test_successful_card_marks_paid_once_and_never_stores_the_card_number(): void
    {
        $inv = $this->sentInvoice();
        $data = ['card_number' => SandboxGateway::SUCCESS_CARD, 'expiry' => '12/30', 'cvc' => '123'];

        $this->post("/pay/{$inv->public_token}", $data);
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertDatabaseHas('payments', ['invoice_id' => $inv->id, 'status' => 'succeeded', 'card_last4' => '4242']);

        // paying again must not create a second successful payment
        $this->post("/pay/{$inv->public_token}", $data);
        $this->assertSame(1, $inv->payments()->where('status', 'succeeded')->count());

        $this->assertDatabaseMissing('payments', ['reference' => SandboxGateway::SUCCESS_CARD]);
    }
}
