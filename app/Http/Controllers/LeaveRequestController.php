<?php

namespace App\Http\Controllers;

use App\Models\HrAuditLog;
use App\Models\LeaveRequest;
use App\Services\LeaveAvailabilitySync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Freelancer side: request leave and cancel it. */
class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        return view('leave.index', [
            'requests' => LeaveRequest::with('reviewer')
                ->where('user_id', $request->user()->id)
                ->latest('start_date')
                ->get(),
            'types' => LeaveRequest::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', Rule::in(LeaveRequest::TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $overlaps = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->withErrors([
                'start_date' => 'These dates overlap a leave request you already have.',
            ]);
        }

        $leave = LeaveRequest::create($data + ['user_id' => $user->id, 'status' => 'pending']);
        HrAuditLog::record('leave.requested', $leave, ['days' => $leave->days()]);

        return back()->with('success', 'Leave request sent to HR.');
    }

    public function cancel(Request $request, LeaveRequest $leave, LeaveAvailabilitySync $sync)
    {
        abort_unless($leave->user_id === $request->user()->id, 403);

        if (! in_array($leave->status, ['pending', 'approved'], true) || $leave->end_date->isPast()) {
            return back()->withErrors(['status' => 'This request can no longer be cancelled.']);
        }

        $leave->update(['status' => 'cancelled']);
        HrAuditLog::record('leave.cancelled', $leave);
        $sync->run();

        return back()->with('success', 'Leave request cancelled.');
    }
}
