<?php

namespace App\Services;

use App\Models\FreelancerProfile;
use App\Models\LeaveRequest;

/**
 * Keeps FreelancerProfile.availability in step with approved leave.
 * Resat's freelancer search already filters on that column, so nothing else needs to change.
 *
 * Safe to run many times: it only changes rows that are out of date.
 */
class LeaveAvailabilitySync
{
    /** @return int number of profiles changed */
    public function run(): int
    {
        $today = now()->toDateString();
        $changed = 0;

        $onLeave = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->pluck('user_id')
            ->unique();

        // 1. Anyone on leave today becomes unavailable (remember what they were).
        FreelancerProfile::whereIn('user_id', $onLeave)->get()->each(function (FreelancerProfile $p) use (&$changed) {
            if ($p->availability_before_leave === null) {
                $p->availability_before_leave = $p->availability;
            }
            if ($p->availability !== 'unavailable') {
                $p->availability = 'unavailable';
                $changed++;
            }
            $p->save();
        });

        // 2. Anyone whose leave is over (or was cancelled) gets their old availability back.
        FreelancerProfile::whereNotNull('availability_before_leave')
            ->whereNotIn('user_id', $onLeave)
            ->get()
            ->each(function (FreelancerProfile $p) use (&$changed) {
                $p->availability = $p->availability_before_leave;
                $p->availability_before_leave = null;
                $p->save();
                $changed++;
            });

        return $changed;
    }
}
