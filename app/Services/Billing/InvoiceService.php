<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceNumber()
    {
        $prefix = config('billing.invoice_number_prefix', 'INV');

        return DB::transaction(function () use ($prefix) {
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $suffix = strtoupper(bin2hex(random_bytes(3)));
                $number = $prefix.'-'.$suffix;

                $exists = Invoice::withTrashed()->where('invoice_number', $number)->lockForUpdate()->exists();
                if (! $exists) {
                    return $number;
                }
            }

            throw new \RuntimeException('Gagal menghasilkan nomor invoice unik.');
        });
    }

    public function createForSubscription(Subscription $subscription, array $overrides = [])
    {
        $subscription->loadMissing(['plan', 'application']);

        $defaults = $this->resolveScheduleDefaults($subscription);
        $overrides = array_merge($defaults, $overrides);

        $amount = $overrides['amount'] ?? $subscription->price_amount;

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'period_start' => $overrides['period_start'],
            'period_end' => $overrides['period_end'],
            'amount' => $amount,
            'amount_paid' => 0,
            'due_date' => $overrides['due_date'],
            'status' => Invoice::STATUS_UNPAID,
            'notes' => $overrides['notes'] ?? null,
        ]);

        $invoice->items()->create([
            'description' => 'Tagihan '.$subscription->plan->name.' — '.$subscription->application->name,
            'amount' => $amount,
        ]);

        return $invoice;
    }

    /**
     * Jatuh tempo & periode mengikuti jadwal langganan (next_billing_date), bukan hari ini +7.
     *
     * @return array{due_date: string, period_start: string, period_end: string}
     */
    public function resolveScheduleDefaults(Subscription $subscription)
    {
        /** @var SubscriptionBillingService $billing */
        $billing = app(SubscriptionBillingService::class);

        $due = $subscription->next_billing_date
            ? $subscription->next_billing_date->copy()->startOfDay()
            : null;

        if (! $due && $subscription->plan && $subscription->starts_at) {
            $due = $billing->nextAnniversaryDueDate(
                $subscription->starts_at->copy(),
                $subscription->plan,
                now()->startOfDay()
            );
        }

        if (! $due) {
            $due = now()->startOfDay();
        }

        $period = $billing->periodForDueDate($subscription, $due);

        return [
            'due_date' => $due->toDateString(),
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
        ];
    }

    public function refreshStatus(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_VOID) {
            return $invoice;
        }

        $paid = (float) $invoice->amount_paid;
        $total = (float) $invoice->amount;

        if ($paid >= $total && $total > 0) {
            $status = Invoice::STATUS_PAID;
        } elseif ($paid > 0) {
            $status = Invoice::STATUS_PARTIAL;
        } elseif ($invoice->due_date->isPast()) {
            $status = Invoice::STATUS_OVERDUE;
        } else {
            $status = Invoice::STATUS_UNPAID;
        }

        $invoice->update(['status' => $status]);

        $invoice = $invoice->fresh();

        if ($invoice->subscription_id) {
            app(SubscriptionBillingService::class)->refreshSubscriptionStatus($invoice->subscription);
        }

        return $invoice;
    }
}
