<?php

namespace App\Console\Commands;

use App\Services\LeaveAvailabilitySync;
use Illuminate\Console\Command;

class SyncLeaveAvailability extends Command
{
    protected $signature = 'leave:sync';

    protected $description = 'Set freelancers unavailable while on approved leave and restore them afterwards';

    public function handle(LeaveAvailabilitySync $sync): int
    {
        $this->info('Profiles updated: ' . $sync->run());

        return self::SUCCESS;
    }
}
