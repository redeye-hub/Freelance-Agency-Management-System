<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\HrAuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Freelancer side: read, accept or decline contracts and download the PDF. */
class MyContractController extends Controller
{
    public function index(Request $request)
    {
        return view('my-contracts.index', [
            'contracts' => Contract::with('assignment.project.client')
                ->whereHas('assignment', fn ($q) => $q->where('user_id', $request->user()->id))
                ->latest()
                ->get(),
        ]);
    }

    public function accept(Request $request, Contract $contract)
    {
        $this->authorizeOwn($request, $contract);

        if ($contract->status !== 'sent') {
            return back()->withErrors(['status' => 'This contract was already answered.']);
        }

        $contract->update(['status' => 'accepted', 'responded_at' => now(), 'response_note' => null]);
        HrAuditLog::record('contract.accepted', $contract);

        return back()->with('success', "You accepted contract {$contract->number}.");
    }

    public function decline(Request $request, Contract $contract)
    {
        $this->authorizeOwn($request, $contract);

        $data = $request->validate(['response_note' => ['required', 'string', 'max:1000']]);

        if ($contract->status !== 'sent') {
            return back()->withErrors(['status' => 'This contract was already answered.']);
        }

        $contract->update(['status' => 'declined', 'responded_at' => now(), 'response_note' => $data['response_note']]);
        HrAuditLog::record('contract.declined', $contract, ['note' => $data['response_note']]);

        return back()->with('success', 'You declined the contract. HR will contact you.');
    }

    public function pdf(Request $request, Contract $contract)
    {
        $this->authorizeOwn($request, $contract);
        $contract->load('assignment.project.client', 'assignment.user');

        return Pdf::loadView('hr.contracts.pdf', ['contract' => $contract])
            ->download("contract-{$contract->number}.pdf");
    }

    private function authorizeOwn(Request $request, Contract $contract): void
    {
        abort_unless($contract->assignment->user_id === $request->user()->id, 403);
    }
}
