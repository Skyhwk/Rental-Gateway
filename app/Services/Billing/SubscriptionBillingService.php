<?php

namespace App\Services\Billing;

use App\Models\AccessLog;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\AccessControlService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionBillingService
{
    /** @var InvoiceService */
    protected $invoices;

    /** @var AccessControlService */
    protected $accessControl;

    public function __construct(InvoiceService $invoices, AccessControlService $accessControl)
    {
        $this->invoices = $invoices;
        $this->accessControl = $accessControl;
    }

    /**
     * @return array{generated: int, skipped: int, statuses_refreshed: int, suspended: int}
     */
    public function processDailyCycles()
    {
        $statusesRefreshed = $this->refreshOpenInvoiceStatuses();
        $generate = $this->generateRecurringInvoices();

        return [
            'generated' => $generate['generated'],
            'skipped' => $generate['skipped'],
            'statuses_refreshed' => $statusesRefreshed,
            'suspended' => 0,
        ];
    }

    /**
     * Refresh status invoice + suspend (dijadwalkan ~09:00).
     */
    public function processScheduledSuspensions()
    {
        $statusesRefreshed = $this->refreshOpenInvoiceStatuses();
        $suspended = $this->enforceGracePeriodSuspensions();

        return [
            'statuses_refreshed' => $statusesRefreshed,
            'suspended' => $suspended,
        ];
    }

    public function refreshOpenInvoiceStatuses()
    {
        $count = 0;

        Invoice::query()
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_VOID])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    $this->invoices->refreshStatus($invoice);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array{generated: int, skipped: int}
     */
    public function generateRecurringInvoices()
    {
        $daysBefore = (int) config('billing.invoice_generate_days_before_due', 7);
        $today = now()->startOfDay();
        $generated = 0;
        $skipped = 0;

        Subscription::query()
            ->with(['plan', 'application'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->whereNotNull('next_billing_date')
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            })
            ->orderBy('id')
            ->chunkById(50, function ($subscriptions) use ($daysBefore, $today, &$generated, &$skipped) {
                foreach ($subscriptions as $subscription) {
                    if (! $subscription->plan || ! $subscription->application) {
                        $skipped++;

                        continue;
                    }

                    $this->ensureNextBillingDate($subscription);

                    if ($this->hasOpenInvoice($subscription)) {
                        $skipped++;

                        continue;
                    }

                    $due = $subscription->next_billing_date->copy()->startOfDay();
                    $generateFrom = $due->copy()->subDays($daysBefore);

                    if ($today->lt($generateFrom)) {
                        continue;
                    }

                    if ($this->invoiceExistsForDueDate($subscription, $due)) {
                        $skipped++;

                        continue;
                    }

                    DB::transaction(function () use ($subscription, $due) {
                        $period = $this->periodForDueDate($subscription, $due);
                        $this->invoices->createForSubscription($subscription, [
                            'due_date' => $due->toDateString(),
                            'period_start' => $period['start']->toDateString(),
                            'period_end' => $period['end']->toDateString(),
                            'notes' => 'Dibuat otomatis (H-'.(int) config('billing.invoice_generate_days_before_due', 7).' jatuh tempo)',
                        ]);
                        $this->advanceNextBillingDate($subscription);
                    });

                    $generated++;
                }
            });

        return compact('generated', 'skipped');
    }

    public function enforceGracePeriodSuspensions()
    {
        if (! config('billing.auto_suspend_on_grace_expired', true)) {
            return 0;
        }

        $now = now();
        $suspended = 0;

        Subscription::query()
            ->with(['application', 'invoices'])
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->orderBy('id')
            ->chunkById(50, function ($subscriptions) use ($now, &$suspended) {
                foreach ($subscriptions as $subscription) {
                    $application = $subscription->application;
                    if (! $application || $application->access_status === Application::ACCESS_SUSPENDED) {
                        continue;
                    }

                    if (! $this->shouldSuspendForBilling($subscription, $now)) {
                        continue;
                    }

                    try {
                        $this->accessControl->changeStatus(
                            $application,
                            Application::ACCESS_SUSPENDED,
                            'Masa tenggang tagihan habis — langganan #'.$subscription->id,
                            null,
                            AccessLog::SOURCE_SYSTEM
                        );
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        continue;
                    }

                    if ($subscription->status !== Subscription::STATUS_PAST_DUE) {
                        $subscription->update(['status' => Subscription::STATUS_PAST_DUE]);
                    }

                    $suspended++;
                }
            });

        return $suspended;
    }

    public function firstBillingDueDate(SubscriptionPlan $plan, Carbon $startsAt)
    {
        return $this->nextDueDateAfter($startsAt->copy()->startOfDay(), $plan);
    }

    public function nextAnniversaryDueDate(Carbon $startsAt, SubscriptionPlan $plan, Carbon $onOrAfter)
    {
        $due = $this->firstBillingDueDate($plan, $startsAt);
        $onOrAfter = $onOrAfter->copy()->startOfDay();

        while ($due->lt($onOrAfter)) {
            $due = $this->nextDueDateAfter($due, $plan);
        }

        return $due;
    }

    public function ensureNextBillingDate(Subscription $subscription)
    {
        if ($subscription->next_billing_date || ! $subscription->plan || ! $subscription->starts_at) {
            return $subscription;
        }

        $next = $this->firstBillingDueDate($subscription->plan, $subscription->starts_at->copy());
        $subscription->update(['next_billing_date' => $next->toDateString()]);

        return $subscription->fresh();
    }

    public function advanceNextBillingDate(Subscription $subscription)
    {
        if (! $subscription->next_billing_date || ! $subscription->plan) {
            return $subscription;
        }

        $next = $this->nextDueDateAfter(
            $subscription->next_billing_date->copy(),
            $subscription->plan
        );

        $subscription->update(['next_billing_date' => $next->toDateString()]);

        return $subscription->fresh();
    }

    /**
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon}
     */
    public function periodForDueDate(Subscription $subscription, Carbon $dueDate)
    {
        $plan = $subscription->plan;
        $end = $dueDate->copy()->startOfDay();
        $start = $this->previousDueDate($end, $plan);

        return ['start' => $start, 'end' => $end];
    }

    public function nextDueDateAfter(Carbon $dueDate, SubscriptionPlan $plan)
    {
        $dueDate = $dueDate->copy()->startOfDay();

        if ($plan->billing_interval === SubscriptionPlan::INTERVAL_YEAR) {
            return $dueDate->addYear();
        }

        return $dueDate->addMonth();
    }

    protected function previousDueDate(Carbon $dueDate, SubscriptionPlan $plan)
    {
        $dueDate = $dueDate->copy()->startOfDay();

        if ($plan->billing_interval === SubscriptionPlan::INTERVAL_YEAR) {
            return $dueDate->subYear();
        }

        return $dueDate->subMonth();
    }

    protected function hasOpenInvoice(Subscription $subscription)
    {
        return $subscription->invoices()
            ->whereIn('status', [
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_PARTIAL,
                Invoice::STATUS_OVERDUE,
            ])
            ->exists();
    }

    protected function invoiceExistsForDueDate(Subscription $subscription, Carbon $dueDate)
    {
        return $subscription->invoices()
            ->whereDate('due_date', $dueDate->toDateString())
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->exists();
    }

    protected function shouldSuspendForBilling(Subscription $subscription, Carbon $now)
    {
        if (! $this->hasOpenInvoice($subscription)) {
            return false;
        }

        $graceDays = (int) $subscription->grace_days;

        foreach ($subscription->invoices as $invoice) {
            if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_VOID], true)) {
                continue;
            }

            if ($this->isPastGraceDeadline($invoice->due_date, $graceDays, $now)) {
                return true;
            }
        }

        return false;
    }

    public function suspendDeadlineForDueDate(Carbon $dueDate, $graceDays)
    {
        $time = config('billing.suspend_at_time', '09:00');
        $parts = explode(':', $time);
        $hour = (int) ($parts[0] ?? 9);
        $minute = (int) ($parts[1] ?? 0);

        $dueDate = $dueDate->copy()->startOfDay();
        $graceDays = (int) $graceDays;

        if ($graceDays <= 0) {
            return $dueDate->copy()->setTime($hour, $minute, 0);
        }

        return $dueDate->copy()->addDays($graceDays + 1)->setTime($hour, $minute, 0);
    }

    protected function isPastGraceDeadline(Carbon $anchorDate, $graceDays, Carbon $now)
    {
        return $now->gte($this->suspendDeadlineForDueDate($anchorDate, $graceDays));
    }
}
