<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\SubscriptionBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Subscription::class);

        $query = Subscription::with(['client', 'application', 'plan']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->get('q'))) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('client', function ($c) use ($like) {
                    $c->where('company_name', 'like', $like);
                })->orWhereHas('application', function ($a) use ($like) {
                    $a->where('name', 'like', $like);
                })->orWhereHas('plan', function ($p) use ($like) {
                    $p->where('name', 'like', $like);
                });
            });
        }

        $subscriptions = $query
            ->orderByRaw('CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [
                Subscription::STATUS_PAST_DUE,
                Subscription::STATUS_ACTIVE,
            ])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $recap = [
            'active' => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            'past_due' => Subscription::where('status', Subscription::STATUS_PAST_DUE)->count(),
            'total' => Subscription::count(),
        ];

        return view('billing.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'statuses' => Subscription::statuses(),
            'recap' => $recap,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Subscription::class);

        return view('billing.subscriptions.create', [
            'subscription' => new Subscription([
                'starts_at' => now()->toDateString(),
                'status' => Subscription::STATUS_ACTIVE,
                'grace_days' => 3,
            ]),
            'clients' => Client::orderBy('company_name')->get(),
            'applications' => Application::with('client')->orderBy('name')->get(),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
            'statuses' => Subscription::statuses(),
        ]);
    }

    public function store(Request $request, InvoiceService $invoiceService, SubscriptionBillingService $billing)
    {
        $this->authorize('create', Subscription::class);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'application_id' => ['required', 'exists:applications,id'],
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['required', 'in:'.implode(',', array_keys(Subscription::statuses()))],
            'notes' => ['nullable', 'string'],
            'create_initial_invoice' => ['sometimes', 'boolean'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);

        $application = Application::findOrFail($data['application_id']);
        if ((int) $application->client_id !== (int) $data['client_id']) {
            return back()->withInput()->with('error', 'Aplikasi tidak milik client yang dipilih.');
        }

        $startsAt = Carbon::parse($data['starts_at']);
        $firstDue = $billing->firstBillingDueDate($plan, $startsAt);

        $subscription = DB::transaction(function () use ($data, $plan, $firstDue) {
            return Subscription::create([
                'client_id' => $data['client_id'],
                'application_id' => $data['application_id'],
                'subscription_plan_id' => $plan->id,
                'price_amount' => $data['price_amount'],
                'starts_at' => $data['starts_at'],
                'next_billing_date' => $firstDue->toDateString(),
                'grace_days' => $data['grace_days'] ?? 0,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        if ($request->boolean('create_initial_invoice')) {
            $subscription->load(['plan', 'application']);
            $due = $subscription->next_billing_date;
            $period = $billing->periodForDueDate($subscription, $due->copy());
            $invoiceService->createForSubscription($subscription, [
                'due_date' => $due->toDateString(),
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
            ]);
            $billing->advanceNextBillingDate($subscription);
        }

        return redirect()
            ->route('billing.subscriptions.show', $subscription)
            ->with('success', 'Langganan dibuat.');
    }

    public function edit(Subscription $subscription)
    {
        $this->authorize('update', $subscription);

        $subscription->load(['client', 'application']);

        return view('billing.subscriptions.edit', [
            'subscription' => $subscription,
            'clients' => Client::orderBy('company_name')->get(),
            'applications' => Application::with('client')->orderBy('name')->get(),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
            'statuses' => Subscription::statuses(),
        ]);
    }

    public function update(Request $request, Subscription $subscription, SubscriptionBillingService $billing)
    {
        $this->authorize('update', $subscription);

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'status' => ['required', 'in:'.implode(',', array_keys(Subscription::statuses()))],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $startsAt = Carbon::parse($data['starts_at']);
        $planChanged = (int) $subscription->subscription_plan_id !== (int) $data['subscription_plan_id'];
        $startsChanged = $subscription->starts_at->toDateString() !== $data['starts_at'];

        $nextBilling = $subscription->next_billing_date;
        if (! $nextBilling || $planChanged || $startsChanged) {
            $nextBilling = $billing->nextAnniversaryDueDate($startsAt, $plan, now()->startOfDay());
        }

        $subscription->update([
            'subscription_plan_id' => $data['subscription_plan_id'],
            'price_amount' => $data['price_amount'],
            'starts_at' => $data['starts_at'],
            'ends_at' => null,
            'next_billing_date' => $nextBilling->toDateString(),
            'grace_days' => $data['grace_days'] ?? 0,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('billing.subscriptions.show', $subscription)
            ->with('success', 'Langganan diperbarui.');
    }

    public function show(Subscription $subscription)
    {
        $this->authorize('view', $subscription);

        $subscription->load(['client', 'application', 'plan', 'invoices']);

        $billingScheduleHint = null;
        if ($subscription->plan && $subscription->starts_at) {
            $billingScheduleHint = app(SubscriptionBillingService::class)
                ->firstBillingDueDate($subscription->plan, $subscription->starts_at->copy());
        }

        return view('billing.subscriptions.show', compact('subscription', 'billingScheduleHint'));
    }
}
