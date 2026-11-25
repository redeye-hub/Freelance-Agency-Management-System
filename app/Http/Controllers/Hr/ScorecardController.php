<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\User;

/** HR keeps the performance scorecard of every freelancer. */
class ScorecardController extends Controller
{
    public function index()
    {
        $stats = PerformanceReview::selectRaw(
            'freelancer_id, count(*) as reviews, avg(quality) as q, avg(communication) as c, avg(timeliness) as t'
        )->groupBy('freelancer_id')->get()->keyBy('freelancer_id');

        $rows = User::role('freelancer')->orderBy('name')->get()->map(function (User $u) use ($stats) {
            $s = $stats->get($u->id);

            return [
                'user' => $u,
                'reviews' => $s?->reviews ?? 0,
                'quality' => $s ? round($s->q, 1) : null,
                'communication' => $s ? round($s->c, 1) : null,
                'timeliness' => $s ? round($s->t, 1) : null,
                'overall' => $s ? round(($s->q + $s->c + $s->t) / 3, 1) : null,
            ];
        })->sortByDesc(fn ($r) => $r['overall'] ?? -1)->values();

        return view('hr.scorecards.index', ['rows' => $rows]);
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('freelancer'), 404);

        $reviews = PerformanceReview::with('project', 'reviewer')
            ->where('freelancer_id', $user->id)
            ->latest()
            ->get();

        return view('hr.scorecards.show', [
            'freelancer' => $user,
            'reviews' => $reviews,
            'overall' => $reviews->isEmpty() ? null : round($reviews->avg(fn ($r) => $r->overall()), 1),
        ]);
    }
}
