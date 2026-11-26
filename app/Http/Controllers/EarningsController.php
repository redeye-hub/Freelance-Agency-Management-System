<?php

namespace App\Http\Controllers;

use App\Models\HrAuditLog;
use App\Models\Payout;
use App\Services\EarningsCalculator;
use Illuminate\Http\Request;

/** Freelancer side: see earnings after commission and ask for a payout. */
class EarningsController extends Controller
{
    public function index(Request $request, EarningsCalculator $earnings)
    {
        $user = $request->user();
        $summary = $earnings->unpaidSummary($user->id);

        return view('earnings.index', [
            'summary' => $summary,
            'unpaidNet' => round((float) $summary->sum('net'), 2),
            'payouts' => Payout::where('user_id', $user->id)->latest()->get(),
            'lifetimePaid' => (float) Payout::where('user_id', $user->id)->where('status', 'paid')->sum('net'),
        ]);
    }

    public function requestPayout(Request $request, EarningsCalculator $earnings)
    {
        $payout = $earnings->createPayout($request->user(), $request->user());

        if (! $payout) {
            return back()->withErrors(['payout' => 'You have no approved, unpaid hours yet. Hours count once your timesheet is approved.']);
        }

        HrAuditLog::record('payout.requested', $payout, ['net' => (string) $payout->net]);

        return back()->with('success', "Payout {$payout->number} requested: \${$payout->net} after commission. Finance will review it.");
    }
}
