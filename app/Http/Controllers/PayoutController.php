<?php

namespace App\Http\Controllers;

use App\Models\HrAuditLog;
use App\Models\Payout;
use App\Models\User;
use App\Services\EarningsCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Finance side: approve, pay or reject payouts, and see what is still unpaid. */
class PayoutController extends Controller
{
    public function index(Request $request, EarningsCalculator $earnings)
    {
        $payouts = Payout::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // Who has approved hours that nobody has turned into a payout yet.
        $unpaid = User::role('freelancer')->orderBy('name')->get()
            ->map(fn (User $u) => ['user' => $u, 'net' => $earnings->unpaidNet($u->id)])
            ->filter(fn ($r) => $r['net'] > 0)
            ->values();

        return view('payouts.index', [
            'payouts' => $payouts,
            'unpaid' => $unpaid,
            'statuses' => Payout::STATUSES,
            'pendingNet' => (float) Payout::where('status', 'pending')->sum('net'),
            'approvedNet' => (float) Payout::where('status', 'approved')->sum('net'),
            'paidNet' => (float) Payout::where('status', 'paid')->sum('net'),
        ]);
    }

    public function show(Payout $payout)
    {
        $payout->load('user', 'lines.project', 'requester', 'reviewer');

        return view('payouts.show', ['payout' => $payout, 'methods' => Payout::METHODS]);
    }

    /** Finance can create a payout on behalf of a freelancer. */
    public function store(Request $request, EarningsCalculator $earnings)
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);

        $freelancer = User::findOrFail($data['user_id']);
        abort_unless($freelancer->hasRole('freelancer'), 422);

        $payout = $earnings->createPayout($freelancer, $request->user());
        if (! $payout) {
            return back()->withErrors(['payout' => "{$freelancer->name} has no unpaid approved hours."]);
        }
        HrAuditLog::record('payout.created', $payout, ['net' => (string) $payout->net]);

        return redirect()->route('payouts.show', $payout)->with('success', "Payout {$payout->number} created.");
    }

    public function approve(Request $request, Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending payouts can be approved.']);
        }

        $payout->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        HrAuditLog::record('payout.approved', $payout, ['net' => (string) $payout->net]);

        return back()->with('success', "Payout {$payout->number} approved. Send the money, then mark it as paid.");
    }

    public function markPaid(Request $request, Payout $payout)
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(Payout::METHODS)],
            'reference' => ['required', 'string', 'max:255'],
        ]);

        if ($payout->status !== 'approved') {
            return back()->withErrors(['status' => 'Approve the payout before marking it as paid.']);
        }

        $payout->update($data + ['status' => 'paid', 'paid_at' => now()]);
        HrAuditLog::record('payout.paid', $payout, ['method' => $data['method'], 'reference' => $data['reference']]);

        return back()->with('success', "Payout {$payout->number} marked as paid.");
    }

    public function reject(Request $request, Payout $payout, EarningsCalculator $earnings)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        if (! in_array($payout->status, ['pending', 'approved'], true)) {
            return back()->withErrors(['status' => 'Only pending or approved payouts can be rejected.']);
        }

        $payout->update(['status' => 'rejected', 'note' => $data['note'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $earnings->release($payout); // the hours become payable again
        HrAuditLog::record('payout.rejected', $payout, ['note' => $data['note']]);

        return back()->with('success', "Payout {$payout->number} rejected. Its hours can be paid out again.");
    }
}
