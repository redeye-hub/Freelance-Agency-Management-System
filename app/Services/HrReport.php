<?php

namespace App\Services;

use App\Http\Controllers\AssignmentController;
use App\Models\Candidate;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\ProjectAssignment;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;

/**
 * Numbers for the HR reports page (web, CSV and PDF all use this).
 *
 *   funnel      = candidates who applied in the period, by their CURRENT stage
 *   leave       = approved leave days that fall inside the period
 *   utilization = hours planned per week (active assignments) against the weekly capacity, plus hours logged in the period
 *   ratings     = performance reviews saved in the period
 */
class HrReport
{
    public function build(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        return [
            'from' => $from,
            'to' => $to,
            'funnel' => $this->funnel($start, $end),
            'leave' => $this->leave($start, $end),
            'utilization' => $this->utilization($start, $end),
            'ratings' => $this->ratings($start, $end),
        ];
    }

    private function funnel(Carbon $start, Carbon $end): array
    {
        $counts = Candidate::whereBetween('created_at', [$start, $end])
            ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage');

        $stages = [];
        foreach (Candidate::STAGES as $s) {
            $stages[$s] = (int) ($counts[$s] ?? 0);
        }
        $total = array_sum($stages);

        return [
            'stages' => $stages,
            'total' => $total,
            'approval_rate' => $total ? round($stages['approved'] / $total * 100, 1) : 0.0,
            'rejection_rate' => $total ? round($stages['rejected'] / $total * 100, 1) : 0.0,
        ];
    }

    private function leave(Carbon $start, Carbon $end): array
    {
        $approved = LeaveRequest::with('user')->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        $byType = [];
        $byPerson = [];
        foreach ($approved as $l) {
            // count only the days that fall inside the report period
            $a = $l->start_date->greaterThan($start) ? $l->start_date : $start->copy()->startOfDay();
            $b = $l->end_date->lessThan($end) ? $l->end_date : $end->copy()->startOfDay();
            $days = (int) $a->diffInDays($b) + 1;

            $byType[$l->type] = ($byType[$l->type] ?? 0) + $days;
            $byPerson[$l->user->name] = ($byPerson[$l->user->name] ?? 0) + $days;
        }
        arsort($byPerson);

        return [
            'total_days' => array_sum($byType),
            'requests' => $approved->count(),
            'by_type' => $byType,
            'by_person' => $byPerson,
            'pending_now' => LeaveRequest::where('status', 'pending')->count(),
        ];
    }

    private function utilization(Carbon $start, Carbon $end): array
    {
        $capacity = AssignmentController::WEEKLY_CAPACITY;

        $planned = ProjectAssignment::where('status', 'active')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['planning', 'active']))
            ->selectRaw('user_id, sum(weekly_hours) as hours')->groupBy('user_id')->pluck('hours', 'user_id');

        $logged = Timesheet::whereBetween('week_start', [$start->copy()->startOfWeek()->toDateString(), $end->toDateString()])
            ->withSum('entries as hours', 'hours')->get()
            ->groupBy('user_id')->map(fn ($rows) => (float) $rows->sum('hours'));

        $rows = User::role('freelancer')->where('status', 'active')->orderBy('name')->get()->map(function (User $u) use ($planned, $logged, $capacity) {
            $hours = (int) ($planned[$u->id] ?? 0);

            return [
                'name' => $u->name,
                'planned' => $hours,
                'percent' => (int) round($hours / $capacity * 100),
                'logged' => round((float) ($logged[$u->id] ?? 0), 2),
            ];
        })->sortByDesc('percent')->values();

        return [
            'capacity' => $capacity,
            'rows' => $rows,
            'average' => $rows->isEmpty() ? 0 : (int) round($rows->avg('percent')),
            'over' => $rows->where('percent', '>', 100)->count(),
            'idle' => $rows->where('planned', 0)->count(),
        ];
    }

    private function ratings(Carbon $start, Carbon $end): array
    {
        $reviews = PerformanceReview::with('freelancer')->whereBetween('created_at', [$start, $end])->get();

        $people = $reviews->groupBy('freelancer_id')->map(function ($rows) {
            return [
                'name' => $rows->first()->freelancer->name,
                'reviews' => $rows->count(),
                'quality' => round($rows->avg('quality'), 1),
                'communication' => round($rows->avg('communication'), 1),
                'timeliness' => round($rows->avg('timeliness'), 1),
                'overall' => round($rows->avg(fn ($r) => $r->overall()), 1),
            ];
        })->sortByDesc('overall')->values();

        return [
            'count' => $reviews->count(),
            'overall' => $reviews->isEmpty() ? null : round($reviews->avg(fn ($r) => $r->overall()), 1),
            'quality' => $reviews->isEmpty() ? null : round($reviews->avg('quality'), 1),
            'communication' => $reviews->isEmpty() ? null : round($reviews->avg('communication'), 1),
            'timeliness' => $reviews->isEmpty() ? null : round($reviews->avg('timeliness'), 1),
            'people' => $people,
        ];
    }
}
