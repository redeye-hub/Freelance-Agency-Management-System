<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Illuminate\Http\Request;

/** Manager side: approve or send back submitted weekly timesheets. */
class TimesheetReviewController extends Controller
{
    public function index()
    {
        return view('timesheets.review', [
            'pending' => Timesheet::with('user', 'entries.project')
                ->where('status', 'submitted')
                ->orderBy('submitted_at')
                ->get(),
            'recent' => Timesheet::with('user', 'reviewer')
                ->whereIn('status', ['approved', 'rejected'])
                ->latest('reviewed_at')
                ->take(10)
                ->get(),
        ]);
    }

    public function approve(Request $request, Timesheet $timesheet)
    {
        if ($timesheet->status !== 'submitted') {
            return back()->withErrors(['status' => 'Only submitted timesheets can be approved.']);
        }

        $timesheet->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        return back()->with('success', "Approved {$timesheet->user->name}'s timesheet.");
    }

    public function reject(Request $request, Timesheet $timesheet)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);

        if ($timesheet->status !== 'submitted') {
            return back()->withErrors(['status' => 'Only submitted timesheets can be sent back.']);
        }

        $timesheet->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return back()->with('success', "Sent {$timesheet->user->name}'s timesheet back for changes.");
    }
}
