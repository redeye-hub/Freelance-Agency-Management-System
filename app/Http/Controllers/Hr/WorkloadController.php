<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AssignmentController;
use App\Models\LeaveRequest;
use App\Models\ProjectAssignment;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

/** HR view: who is over-allocated, who has room, and who is on leave. */
class WorkloadController extends Controller
{
    public function index()
    {
        $capacity = AssignmentController::WEEKLY_CAPACITY;
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $assignments = ProjectAssignment::with('project')
            ->where('status', 'active')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['planning', 'active']))
            ->get()
            ->groupBy('user_id');

        $logged = Timesheet::where('week_start', $weekStart)
            ->withSum('entries as hours', 'hours')
            ->get()
            ->pluck('hours', 'user_id');

        $onLeave = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $today)->where('end_date', '>=', $today)
            ->pluck('end_date', 'user_id');

        $rows = User::role('freelancer')->where('status', 'active')->orderBy('name')->get()->map(function (User $u) use ($assignments, $logged, $onLeave, $capacity) {
            $mine = $assignments->get($u->id, collect());
            $planned = (int) $mine->sum('weekly_hours');
            $percent = (int) round($planned / $capacity * 100);

            return [
                'user' => $u,
                'projects' => $mine->pluck('project.name'),
                'planned' => $planned,
                'percent' => $percent,
                'logged' => (float) ($logged[$u->id] ?? 0),
                'on_leave_until' => $onLeave[$u->id] ?? null,
                'level' => match (true) {
                    $percent > 100 => 'over',
                    $percent >= 80 => 'high',
                    $percent === 0 => 'free',
                    default => 'ok',
                },
            ];
        })->sortByDesc('percent')->values();

        return view('hr.workload.index', [
            'rows' => $rows,
            'capacity' => $capacity,
            'overCount' => $rows->where('level', 'over')->count(),
            'freeCount' => $rows->where('level', 'free')->count(),
        ]);
    }
}
