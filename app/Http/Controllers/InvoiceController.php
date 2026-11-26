<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\HrAuditLog;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Finance side: build invoices from milestones and approved hours, send them, record payment. */
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        $invoices = Invoice::with('client', 'project')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")
                ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$s}%"))))
            ->when($request->status === 'overdue', fn ($q) => $q->where('status', 'sent')->where('due_date', '<', $today))
            ->when($request->status && $request->status !== 'overdue', fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'outstanding' => (float) Invoice::where('status', 'sent')->sum('total'),
            'overdue' => (float) Invoice::where('status', 'sent')->where('due_date', '<', $today)->sum('total'),
            'paidThisMonth' => (float) Invoice::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
        ]);
    }

    /** Step 1 shows a project picker. Step 2 (with ?project=ID) lists what can still be billed. */
    public function create(Request $request)
    {
        $project = $request->filled('project') ? Project::with('client')->find($request->project) : null;

        $milestones = collect();
        $hourGroups = collect();

        if ($project) {
            $milestones = $project->milestones()->whereNull('invoice_id')->orderBy('due_date')->get();
            $hourGroups = $this->unbilledHours($project);
        }

        return view('invoices.create', [
            'projects' => Project::with('client')->where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'project' => $project,
            'milestones' => $milestones,
            'hourGroups' => $hourGroups,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'milestone_ids' => ['nullable', 'array'],
            'milestone_ids.*' => ['integer'],
            'hour_users' => ['nullable', 'array'],
            'hour_users.*' => ['integer'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = Project::with('client')->findOrFail($data['project_id']);

        $invoice = DB::transaction(function () use ($data, $project, $request, &$error) {
            $lines = [];
            $milestoneIds = array_unique($data['milestone_ids'] ?? []);
            $hourUsers = array_unique($data['hour_users'] ?? []);

            // 1. milestones (locked so two finance users cannot bill the same one)
            $milestones = Milestone::where('project_id', $project->id)
                ->whereIn('id', $milestoneIds)->whereNull('invoice_id')->lockForUpdate()->get();
            if ($milestones->count() !== count($milestoneIds)) {
                $error = 'One of the selected milestones was already invoiced. Reload and try again.';

                return null;
            }
            foreach ($milestones as $m) {
                $lines[] = ['description' => "Milestone: {$m->title}", 'quantity' => 1, 'unit_price' => (float) $m->amount];
            }

            // 2. approved, not yet billed hours, one line per freelancer
            $entryIds = [];
            $rates = ProjectAssignment::with('user')->where('project_id', $project->id)->get()->keyBy('user_id');
            foreach ($hourUsers as $userId) {
                $entries = $this->unbilledEntries($project->id, (int) $userId)->lockForUpdate()->get();
                $assignment = $rates->get((int) $userId);
                if ($entries->isEmpty() || ! $assignment) {
                    $error = 'One of the selected freelancers has no billable hours left. Reload and try again.';

                    return null;
                }
                $hours = (float) $entries->sum('hours');
                $lines[] = [
                    'description' => sprintf(
                        '%s: %s h approved work (%s to %s)',
                        $assignment->user->name ?? 'Freelancer',
                        rtrim(rtrim(number_format($hours, 2), '0'), '.'),
                        $entries->min('work_date')->format('d M'),
                        $entries->max('work_date')->format('d M Y')
                    ),
                    'quantity' => $hours,
                    'unit_price' => (float) $assignment->hourly_rate,
                ];
                $entryIds = array_merge($entryIds, $entries->pluck('id')->all());
            }

            if ($lines === []) {
                $error = 'Select at least one milestone or one freelancer\'s hours.';

                return null;
            }

            // 3. totals (rounded per line, tax on the subtotal)
            $subtotal = 0;
            foreach ($lines as &$line) {
                $line['amount'] = round($line['quantity'] * $line['unit_price'], 2);
                $subtotal += $line['amount'];
            }
            unset($line);
            $subtotal = round($subtotal, 2);
            $tax = round($subtotal * (float) $data['tax_percent'] / 100, 2);

            $invoice = Invoice::create([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => $data['due_date'],
                'subtotal' => $subtotal,
                'tax_percent' => $data['tax_percent'],
                'tax_amount' => $tax,
                'total' => round($subtotal + $tax, 2),
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $invoice->update(['number' => sprintf('INV-%s-%04d', now()->format('Y'), $invoice->id)]);
            $invoice->items()->createMany($lines);

            // 4. mark what is now billed
            Milestone::whereIn('id', $milestones->pluck('id'))->update(['invoice_id' => $invoice->id]);
            TimeEntry::whereIn('id', $entryIds)->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        if (! $invoice) {
            return back()->withInput()->withErrors(['project_id' => $error ?? 'Could not create the invoice.']);
        }

        HrAuditLog::record('invoice.created', $invoice, ['total' => (string) $invoice->total]);

        return redirect()->route('invoices.show', $invoice)->with('success', "Invoice {$invoice->number} created as a draft.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'client', 'project', 'payments.recorder', 'creator');

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function send(Invoice $invoice)
    {
        if (! in_array($invoice->status, ['draft', 'sent'], true)) {
            return back()->withErrors(['status' => 'Only draft or sent invoices can be emailed.']);
        }
        if (! $invoice->client->email) {
            return back()->withErrors(['status' => 'This client has no email address.']);
        }

        Mail::to($invoice->client->email)->send(new InvoiceMail($invoice));

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        }
        HrAuditLog::record('invoice.sent', $invoice, ['to' => $invoice->client->email]);

        return back()->with('success', "Invoice emailed to {$invoice->client->email}.");
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('items', 'client', 'project');

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->download("invoice-{$invoice->number}.pdf");
    }

    /** Record an offline payment (bank transfer, cash). */
    public function markPaid(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['reference' => ['nullable', 'string', 'max:255']]);

        if (! in_array($invoice->status, ['draft', 'sent'], true)) {
            return back()->withErrors(['status' => 'This invoice cannot be marked as paid.']);
        }

        DB::transaction(function () use ($invoice, $data, $request) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
                'method' => 'manual',
                'status' => 'succeeded',
                'reference' => $data['reference'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        });
        HrAuditLog::record('invoice.paid_manual', $invoice, ['reference' => $data['reference'] ?? null]);

        return back()->with('success', 'Payment recorded.');
    }

    /** Voiding frees the milestones and hours so they can be billed again. */
    public function void(Invoice $invoice)
    {
        if (! in_array($invoice->status, ['draft', 'sent'], true)) {
            return back()->withErrors(['status' => 'A paid or voided invoice cannot be voided.']);
        }

        DB::transaction(function () use ($invoice) {
            Milestone::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            TimeEntry::where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            $invoice->update(['status' => 'void']);
        });
        HrAuditLog::record('invoice.voided', $invoice);

        return back()->with('success', "Invoice {$invoice->number} voided. Its work can be billed again.");
    }

    /** Approved, not yet billed hours for a project, grouped per freelancer with their rate. */
    private function unbilledHours(Project $project)
    {
        $assignments = ProjectAssignment::with('user')->where('project_id', $project->id)->get()->keyBy('user_id');

        return $assignments->map(function (ProjectAssignment $a) use ($project) {
            $entries = $this->unbilledEntries($project->id, $a->user_id)->get();
            $hours = (float) $entries->sum('hours');

            return [
                'user' => $a->user,
                'hours' => $hours,
                'rate' => (float) $a->hourly_rate,
                'amount' => round($hours * (float) $a->hourly_rate, 2),
            ];
        })->filter(fn ($g) => $g['hours'] > 0)->values();
    }

    private function unbilledEntries(int $projectId, int $userId)
    {
        return TimeEntry::where('project_id', $projectId)
            ->whereNull('invoice_id')
            ->whereHas('timesheet', fn ($q) => $q->where('status', 'approved')->where('user_id', $userId));
    }
}
