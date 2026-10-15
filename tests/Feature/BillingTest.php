<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\SubscriptionBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function billingFixture(): array
    {
        $client = Client::create([
            'company_name' => 'PT Billing',
            'pic_name' => 'B',
            'email' => 'billing@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $application = Application::create([
            'gateway_node_id' => 1,
            'client_id' => $client->id,
            'name' => 'App Bill',
            'slug' => 'app-bill',
            'internal_host' => '127.0.0.1',
            'internal_port' => 1720,
            'access_status' => Application::ACCESS_SUSPENDED,
        ]);
        $application->domains()->create(['hostname' => 'bill.test.local', 'is_primary' => true]);

        $plan = SubscriptionPlan::create([
            'name' => 'Test Plan',
            'billing_interval' => SubscriptionPlan::INTERVAL_MONTH,
            'is_active' => true,
        ]);
        SubscriptionPlanPrice::create([
            'subscription_plan_id' => $plan->id,
            'amount' => 100000,
            'effective_from' => now()->toDateString(),
        ]);

        $subscription = Subscription::create([
            'client_id' => $client->id,
            'application_id' => $application->id,
            'subscription_plan_id' => $plan->id,
            'price_amount' => 100000,
            'starts_at' => now()->toDateString(),
            'next_billing_date' => now()->toDateString(),
            'grace_days' => 3,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $invoice = app(InvoiceService::class)->createForSubscription($subscription);

        return compact('client', 'application', 'subscription', 'invoice');
    }

    public function test_payment_verify_marks_invoice_paid_and_activates_application(): void
    {
        Config::set('billing.auto_activate_on_payment_verified', true);
        Config::set('gateway.agent.enabled', false);

        ['application' => $application, 'invoice' => $invoice, 'subscription' => $subscription] = $this->billingFixture();

        $finance = User::factory()->create();
        $finance->assignRole('Finance');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'verification_status' => Payment::STATUS_PENDING,
        ]);

        app(PaymentService::class)->verify($payment, $finance);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);

        $application->refresh();
        $this->assertSame(Application::ACCESS_ACTIVE, $application->access_status);

        $subscription->refresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    public function test_payment_verify_clears_past_due_subscription_status(): void
    {
        Config::set('billing.auto_activate_on_payment_verified', true);
        Config::set('gateway.agent.enabled', false);

        ['invoice' => $invoice, 'subscription' => $subscription] = $this->billingFixture();

        $subscription->update(['status' => Subscription::STATUS_PAST_DUE, 'grace_days' => 0]);
        $invoice->update([
            'due_date' => now()->toDateString(),
            'status' => Invoice::STATUS_OVERDUE,
            'amount_paid' => 0,
        ]);

        $finance = User::factory()->create();
        $finance->assignRole('Finance');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'verification_status' => Payment::STATUS_PENDING,
        ]);

        app(PaymentService::class)->verify($payment, $finance);

        $subscription->refresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
    }

    public function test_invoice_number_uses_prefix_and_six_hex(): void
    {
        ['invoice' => $invoice] = $this->billingFixture();

        $this->assertMatchesRegularExpression('/^INV-[0-9A-F]{6}$/', $invoice->invoice_number);
    }

    public function test_yearly_subscription_first_due_is_one_year_after_start(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Yearly',
            'billing_interval' => SubscriptionPlan::INTERVAL_YEAR,
            'is_active' => true,
        ]);

        $due = app(SubscriptionBillingService::class)->firstBillingDueDate(
            $plan,
            Carbon::parse('2025-12-01')
        );

        $this->assertSame('2026-12-01', $due->toDateString());
    }

    public function test_suspend_on_due_date_when_grace_zero(): void
    {
        Config::set('billing.auto_suspend_on_grace_expired', true);
        Config::set('gateway.agent.enabled', false);

        Carbon::setTestNow('2026-12-01 10:00:00');

        ['application' => $application, 'subscription' => $subscription, 'invoice' => $invoice] = $this->billingFixture();

        $application->update(['access_status' => Application::ACCESS_ACTIVE]);
        $subscription->update(['grace_days' => 0]);
        $invoice->update([
            'due_date' => '2026-12-01',
            'status' => Invoice::STATUS_UNPAID,
            'amount_paid' => 0,
        ]);

        $suspended = app(SubscriptionBillingService::class)->enforceGracePeriodSuspensions();

        $this->assertSame(1, $suspended);

        Carbon::setTestNow();
    }

    public function test_recurring_invoice_generated_days_before_due(): void
    {
        Config::set('billing.invoice_generate_days_before_due', 7);
        Config::set('gateway.agent.enabled', false);

        ['subscription' => $subscription] = $this->billingFixture();

        $subscription->invoices()->forceDelete();
        $subscription->update(['next_billing_date' => now()->addDays(7)->toDateString()]);

        $result = app(SubscriptionBillingService::class)->generateRecurringInvoices();

        $this->assertSame(1, $result['generated']);
        $this->assertDatabaseHas('invoices', [
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_UNPAID,
        ]);
    }

    public function test_suspend_after_grace_period_without_payment(): void
    {
        Config::set('billing.auto_suspend_on_grace_expired', true);
        Config::set('gateway.agent.enabled', false);

        Carbon::setTestNow('2026-10-20 10:00:00');

        ['application' => $application, 'subscription' => $subscription, 'invoice' => $invoice] = $this->billingFixture();

        $application->update(['access_status' => Application::ACCESS_ACTIVE]);
        $subscription->update(['grace_days' => 3]);
        $invoice->update([
            'due_date' => '2026-10-10',
            'status' => Invoice::STATUS_OVERDUE,
            'amount_paid' => 0,
        ]);

        $suspended = app(SubscriptionBillingService::class)->enforceGracePeriodSuspensions();

        $this->assertSame(1, $suspended);
        $application->refresh();
        $this->assertSame(Application::ACCESS_SUSPENDED, $application->access_status);

        Carbon::setTestNow();
    }

    public function test_finance_can_verify_payment_via_http(): void
    {
        Config::set('gateway.agent.enabled', false);

        ['invoice' => $invoice] = $this->billingFixture();

        $finance = User::factory()->create();
        $finance->assignRole('Finance');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'verification_status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($finance)
            ->post(route('billing.payments.verify', $payment))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'verification_status' => Payment::STATUS_VERIFIED,
        ]);
    }
}
