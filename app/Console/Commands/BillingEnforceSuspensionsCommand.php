<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Console\Command;

class BillingEnforceSuspensionsCommand extends Command
{
    protected $signature = 'billing:enforce-suspensions';

    protected $description = 'Refresh status invoice & suspend aplikasi setelah jatuh tempo + toleransi (jam '.('09:00').' config)';

    public function handle(SubscriptionBillingService $billing)
    {
        $result = $billing->processScheduledSuspensions();

        $this->info(sprintf(
            'Suspend: %d aplikasi (cek jam %s, jalan scheduler %s, tz %s)',
            $result['suspended'],
            config('billing.suspend_at_time', '09:00'),
            config('billing.suspend_run_at', '09:05'),
            config('app.timezone')
        ));

        return 0;
    }
}
