<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the admin / finance dashboard and the finance report (web, CSV and PDF all use this).
 *
 * Definitions, so everyone reads the figures the same way:
 *   revenue    = subtotal of invoices PAID in the period (tax is passed on, so it is not revenue)
 *   cost       = what freelancers earn after commission, for hours in APPROVED timesheets in the period
 *   commission = the agency's cut of those hours
 *   profit     = revenue - cost
 *   outstanding / overdue = sent invoices not yet paid (as of today, not limited to the period)
 */
class FinanceReport
{
    public function build(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $revenueByProject = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('project_id, sum(subtotal) as amount')
            ->groupBy('project_id')->pluck('amount', 'project_id');

        $invoicedByProject = Invoice::where('status', '!=', 'void')
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('project_id, sum(subtotal) as amount')
            ->groupBy('project_id')->pluck('amount', 'project_id');

        $costByProject = $this->costByProject($start, $end);

        $projects = Project::with('client')->orderBy('name')->get()->map(function (Project $p) use ($revenueByProject, $invoicedByProject, $costByProject) {
            $revenue = round((float) ($revenueByProject[$p->id] ?? 0), 2);
            $cost = round((float) ($costByProject[$p->id]['cost'] ?? 0), 2);

            return [
                'project' => $p->name,
                'client' => $p->client->company_name,
                'invoiced' => round((float) ($invoicedByProject[$p->id] ?? 0), 2),
                'revenue' => $revenue,
                'cost' => $cost,
                'commission' => round((float) ($costByProject[$p->id]['commission'] ?? 0), 2),
                'profit' => round($revenue - $cost, 2),
            ];
        })->filter(fn ($r) => $r['invoiced'] || $r['revenue'] || $r['cost'])->values();

        $today = now()->toDateString();

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => round($projects->sum('revenue'), 2),
            'invoiced' => round($projects->sum('invoiced'), 2),
            'cost' => round($projects->sum('cost'), 2),
            'commission' => round($projects->sum('commission'), 2),
            'profit' => round($projects->sum('profit'), 2),
            'outstanding' => round((float) Invoice::where('status', 'sent')->sum('total'), 2),
            'overdue' => round((float) Invoice::where('status', 'sent')->where('due_date', '<', $today)->sum('total'), 2),
            'overdue_count' => Invoice::where('status', 'sent')->where('due_date', '<', $today)->count(),
            'payouts_pending' => round((float) Payout::whereIn('status', ['pending', 'approved'])->sum('net'), 2),
            'projects' => $projects,
            'monthly' => $this->monthlyRevenue($to),
            'overdue_invoices' => Invoice::with('client')->where('status', 'sent')->where('due_date', '<', $today)
                ->orderBy('due_date')->take(8)->get(),
        ];
    }

    /** @return array<int, array{cost: float, commission: float}> keyed by project id */
    private function costByProject(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('time_entries as te')
            ->join('timesheets as ts', 'ts.id', '=', 'te.timesheet_id')
            ->join('project_assignments as pa', function ($j) {
                $j->on('pa.project_id', '=', 'te.project_id')->on('pa.user_id', '=', 'ts.user_id');
            })
            ->where('ts.status', 'approved')
            ->whereBetween('te.work_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('te.project_id')
            ->selectRaw('te.project_id,
                sum(te.hours * pa.hourly_rate) as gross,
                sum(te.hours * pa.hourly_rate * pa.commission_percent / 100) as commission')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->project_id] = [
                'cost' => (float) $r->gross - (float) $r->commission,
                'commission' => (float) $r->commission,
            ];
        }

        return $out;
    }

    /** Paid revenue for the 6 months that end with the month of $to. */
    private function monthlyRevenue(Carbon $to): array
    {
        $first = $to->copy()->startOfMonth()->subMonths(5);

        $paid = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $first)
            ->where('paid_at', '<=', $to->copy()->endOfMonth())
            ->get(['subtotal', 'paid_at']);

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $first->copy()->addMonths($i);
            $months[] = [
                'label' => $m->format('M Y'),
                'amount' => round((float) $paid->filter(fn ($inv) => $inv->paid_at->format('Y-m') === $m->format('Y-m'))->sum('subtotal'), 2),
            ];
        }

        return $months;
    }
}
