<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\HrAuditLog;
use App\Models\ProjectAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** HR side: generate a contract for a project assignment, download it as PDF, withdraw it. */
class ContractController extends Controller
{
    public function index()
    {
        return view('hr.contracts.index', [
            'assignments' => ProjectAssignment::with('project.client', 'user', 'contract')
                ->where('status', 'active')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_assignment_id' => ['required', 'exists:project_assignments,id'],
            'end_date' => ['nullable', 'date', 'after_or_equal:today'],
            'terms' => ['nullable', 'string', 'max:5000'],
        ]);

        $assignment = ProjectAssignment::with('project')->findOrFail($data['project_assignment_id']);

        if ($assignment->status !== 'active') {
            return back()->withErrors(['project_assignment_id' => 'Contracts can only be created for active assignments.']);
        }
        if ($assignment->contract()->exists()) {
            return back()->withErrors(['project_assignment_id' => 'This assignment already has a contract.']);
        }

        // Snapshot the commercial terms as they are right now.
        $contract = Contract::create([
            'project_assignment_id' => $assignment->id,
            'hourly_rate' => $assignment->hourly_rate,
            'commission_percent' => $assignment->commission_percent,
            'weekly_hours' => $assignment->weekly_hours,
            'start_date' => now()->toDateString(),
            'end_date' => $data['end_date'] ?? $assignment->project->deadline?->toDateString(),
            'terms' => $data['terms'] ?: Contract::defaultTerms(),
            'status' => 'sent',
            'sent_at' => now(),
            'created_by' => $request->user()->id,
        ]);
        $contract->update(['number' => sprintf('FAMS-C-%s-%04d', now()->format('Y'), $contract->id)]);

        HrAuditLog::record('contract.generated', $contract, ['assignment_id' => $assignment->id]);

        return back()->with('success', "Contract {$contract->number} sent to the freelancer.");
    }

    public function pdf(Contract $contract)
    {
        $contract->load('assignment.project.client', 'assignment.user');

        return Pdf::loadView('hr.contracts.pdf', ['contract' => $contract])
            ->download("contract-{$contract->number}.pdf");
    }

    /** A contract that was not accepted can be withdrawn so HR can issue a corrected one. */
    public function withdraw(Contract $contract)
    {
        if ($contract->status === 'accepted') {
            return back()->withErrors(['status' => 'An accepted contract cannot be withdrawn.']);
        }

        HrAuditLog::record('contract.withdrawn', $contract, ['number' => $contract->number]);
        $contract->delete();

        return back()->with('success', 'Contract withdrawn.');
    }
}
