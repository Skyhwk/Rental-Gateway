<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Console\Command;

class BillingProcessCyclesCommand extends Command
{
    protected $signature = 'billing:process-cycles';

    protected $description = 'Generate invoice recurring (H-X jatuh tempo), refresh status, suspend jika grace habis';

    public function handle(SubscriptionBillingService $billing)
    {
        $result = $billing->processDailyCycles();

        $this->info(sprintf(
            'Billing: invoice baru %d (skip %d), status diperbarui %d (suspend terpisah jam %s)',
            $result['generated'],
            $result['skipped'],
            $result['statuses_refreshed'],
            config('billing.suspend_at_time', '09:00')
        ));

        return 0;
    }
}
