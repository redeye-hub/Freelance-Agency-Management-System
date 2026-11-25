<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use App\Models\TimeEntry;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Freelancer side: log hours into this week's timesheet, then submit it for approval. */
class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $weekStart = $this->weekStart($request->query('week'));

        $timesheet = Timesheet::firstOrCreate(
            ['user_id' => $user->id, 'week_start' => $weekStart->toDateString()],
            ['status' => 'draft']
        );
        $timesheet->load('entries.project', 'reviewer');

        $projects = ProjectAssignment::with('project')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->pluck('project')
            ->filter();

        return view('timesheets.index', [
            'timesheet' => $timesheet,
            'weekStart' => $weekStart,
            'projects' => $projects,
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'history' => Timesheet::where('user_id', $user->id)->latest('week_start')->take(8)->get(),
        ]);
    }

    public function storeEntry(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'project_id' => [
                'required',
                Rule::exists('project_assignments', 'project_id')
                    ->where('user_id', $user->id)->where('status', 'active'),
            ],
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $weekStart = $this->weekStart($data['work_date']);
        $timesheet = Timesheet::firstOrCreate(
            ['user_id' => $user->id, 'week_start' => $weekStart->toDateString()],
            ['status' => 'draft']
        );

        if (! $timesheet->isEditable()) {
            return back()->withErrors(['hours' => 'This week is already ' . $timesheet->status . ' and cannot be edited.']);
        }

        $dayTotal = (float) $timesheet->entries()->whereDate('work_date', $data['work_date'])->sum('hours');
        if ($dayTotal + (float) $data['hours'] > 24) {
            return back()->withInput()->withErrors(['hours' => 'You cannot log more than 24 hours in one day.']);
        }

        $timesheet->entries()->create($data);

        return redirect()->route('timesheet.index', ['week' => $weekStart->toDateString()])
            ->with('success', 'Hours logged.');
    }

    public function destroyEntry(Request $request, TimeEntry $entry)
    {
        $timesheet = $entry->timesheet;
        abort_unless($timesheet->user_id === $request->user()->id, 403);

        if (! $timesheet->isEditable()) {
            return back()->withErrors(['hours' => 'This week is locked and cannot be edited.']);
        }

        $entry->delete();

        return back()->with('success', 'Entry removed.');
    }

    public function submit(Request $request)
    {
        $data = $request->validate(['week' => ['required', 'date']]);

        $timesheet = Timesheet::where('user_id', $request->user()->id)
            ->where('week_start', $this->weekStart($data['week'])->toDateString())
            ->firstOrFail();

        if (! $timesheet->isEditable()) {
            return back()->withErrors(['hours' => 'This week was already submitted.']);
        }
        if ($timesheet->entries()->count() === 0) {
            return back()->withErrors(['hours' => 'Log at least one entry before submitting.']);
        }

        $timesheet->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'review_note' => null,
        ]);

        return back()->with('success', 'Timesheet submitted for approval.');
    }

    /** Any date (or null for today) becomes the Monday of its week. */
    private function weekStart(?string $date): Carbon
    {
        try {
            $day = $date ? Carbon::parse($date) : now();
        } catch (\Throwable) {
            $day = now();
        }

        return $day->startOfWeek(Carbon::MONDAY)->startOfDay();
    }
}
