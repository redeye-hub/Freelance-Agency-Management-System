<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrAuditLog;
use App\Models\LeaveRequest;
use App\Services\LeaveAvailabilitySync;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** HR side: approve or reject leave, and see who is away on a month calendar. */
class LeaveReviewController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->month($request->query('month'));

        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $approved = LeaveRequest::with('user')
            ->where('status', 'approved')
            ->where('start_date', '<=', $gridEnd->toDateString())
            ->where('end_date', '>=', $gridStart->toDateString())
            ->get();

        // Build the calendar: a list of weeks, each with 7 days and the people away that day.
        $weeks = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $day = $d->copy();
            $weeks[$day->copy()->startOfWeek(Carbon::MONDAY)->toDateString()][] = [
                'date' => $day,
                'inMonth' => $day->month === $month->month,
                'away' => $approved->filter(fn ($l) => $l->start_date->lte($day) && $l->end_date->gte($day))->values(),
            ];
        }

        return view('hr.leave.index', [
            'pending' => LeaveRequest::with('user')->where('status', 'pending')->orderBy('start_date')->get(),
            'recent' => LeaveRequest::with('user', 'reviewer')->whereIn('status', ['approved', 'rejected'])->latest('reviewed_at')->take(10)->get(),
            'weeks' => $weeks,
            'month' => $month,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leave, LeaveAvailabilitySync $sync)
    {
        if ($leave->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be approved.']);
        }

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ]);
        HrAuditLog::record('leave.approved', $leave);
        $sync->run(); // updates availability right away if the leave covers today

        return back()->with('success', "Approved leave for {$leave->user->name}.");
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);

        if ($leave->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be rejected.']);
        }

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);
        HrAuditLog::record('leave.rejected', $leave, ['note' => $data['review_note']]);

        return back()->with('success', "Rejected leave for {$leave->user->name}.");
    }

    private function month(?string $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value . '-01')->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
