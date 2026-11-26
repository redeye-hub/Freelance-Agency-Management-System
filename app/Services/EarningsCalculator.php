<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\PayoutLine;
use App\Models\ProjectAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The money rules for freelancer earnings, in ONE place.
 *
 *   gross      = approved hours x hourly rate of the assignment
 *   commission = gross x commission % of the assignment (kept by the agency)
 *   net        = gross - commission (paid to the freelancer)
 *
 * Only hours from APPROVED timesheets count, and each hour is paid once (time_entries.payout_id).
 */
class EarningsCalculator
{
    /** Approved hours that are not part of any payout yet. */
    public function unpaidEntries(int $userId)
    {
        return TimeEntry::whereNull('payout_id')
            ->whereHas('timesheet', fn ($q) => $q->where('status', 'approved')->where('user_id', $userId));
    }

    /**
     * Earnings still waiting for a payout, one row per project.
     *
     * @return Collection<int, array{project_id:int, project:string, hours:float, rate:float, commission_percent:float, gross:float, commission:float, net:float}>
     */
    public function unpaidSummary(int $userId): Collection
    {
        $entries = $this->unpaidEntries($userId)->with('project')->get();
        $assignments = ProjectAssignment::where('user_id', $userId)->get()->keyBy('project_id');

        return $entries->groupBy('project_id')->map(function (Collection $rows, $projectId) use ($assignments) {
            $a = $assignments->get($projectId);
            if (! $a) {
                return null; // hours on a project the freelancer was never assigned to: cannot be priced
            }

            return $this->line((float) $rows->sum('hours'), (float) $a->hourly_rate, (float) $a->commission_percent) + [
                'project_id' => (int) $projectId,
                'project' => $rows->first()->project->name,
            ];
        })->filter()->values();
    }

    /** Total net amount waiting for a payout. */
    public function unpaidNet(int $userId): float
    {
        return round((float) $this->unpaidSummary($userId)->sum('net'), 2);
    }

    /**
     * Turn all unpaid approved hours of a freelancer into one pending payout.
     * Returns null when there is nothing to pay.
     */
    public function createPayout(User $freelancer, ?User $requestedBy = null): ?Payout
    {
        return DB::transaction(function () use ($freelancer, $requestedBy) {
            // Lock the rows so a double click or two finance users cannot pay the same hours twice.
            $entries = $this->unpaidEntries($freelancer->id)->lockForUpdate()->get();
            $assignments = ProjectAssignment::where('user_id', $freelancer->id)->get()->keyBy('project_id');

            $lines = [];
            $usedIds = [];
            foreach ($entries->groupBy('project_id') as $projectId => $rows) {
                $a = $assignments->get($projectId);
                if (! $a) {
                    continue;
                }
                $lines[] = $this->line((float) $rows->sum('hours'), (float) $a->hourly_rate, (float) $a->commission_percent)
                    + ['project_id' => (int) $projectId];
                $usedIds = array_merge($usedIds, $rows->pluck('id')->all());
            }

            if ($lines === []) {
                return null;
            }

            $payout = Payout::create([
                'user_id' => $freelancer->id,
                'hours' => round(array_sum(array_column($lines, 'hours')), 2),
                'gross' => round(array_sum(array_column($lines, 'gross')), 2),
                'commission' => round(array_sum(array_column($lines, 'commission')), 2),
                'net' => round(array_sum(array_column($lines, 'net')), 2),
                'status' => 'pending',
                'requested_by' => $requestedBy?->id,
            ]);
            $payout->update(['number' => sprintf('PO-%s-%04d', now()->format('Y'), $payout->id)]);

            foreach ($lines as $l) {
                PayoutLine::create([
                    'payout_id' => $payout->id,
                    'project_id' => $l['project_id'],
                    'hours' => $l['hours'],
                    'hourly_rate' => $l['rate'],
                    'commission_percent' => $l['commission_percent'],
                    'gross' => $l['gross'],
                    'commission' => $l['commission'],
                    'net' => $l['net'],
                ]);
            }

            TimeEntry::whereIn('id', $usedIds)->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }

    /** Release the hours of a rejected payout so they can be paid out again. */
    public function release(Payout $payout): void
    {
        TimeEntry::where('payout_id', $payout->id)->update(['payout_id' => null]);
    }

    /** @return array{hours:float, rate:float, commission_percent:float, gross:float, commission:float, net:float} */
    private function line(float $hours, float $rate, float $commissionPercent): array
    {
        $gross = round($hours * $rate, 2);
        $commission = round($gross * $commissionPercent / 100, 2);

        return [
            'hours' => round($hours, 2),
            'rate' => $rate,
            'commission_percent' => $commissionPercent,
            'gross' => $gross,
            'commission' => $commission,
            'net' => round($gross - $commission, 2),
        ];
    }
}
