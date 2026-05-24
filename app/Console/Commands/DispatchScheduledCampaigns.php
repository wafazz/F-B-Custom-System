<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledCampaign;
use App\Models\ScheduledCampaign;
use Illuminate\Console\Command;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch';

    protected $description = 'Send any admin-scheduled push campaigns that are now due';

    public function handle(): int
    {
        $now = now();
        $dispatched = 0;

        foreach (ScheduledCampaign::query()->where('is_active', true)->get() as $campaign) {
            if (! $campaign->isDue($now)) {
                continue;
            }
            // Stamp last_sent_at first so an overlapping run can't fire it twice.
            $campaign->forceFill(['last_sent_at' => $now])->save();
            SendScheduledCampaign::dispatch($campaign->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} campaign(s).");

        return self::SUCCESS;
    }
}
