<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        $plans = SubscriptionPlan::with('prices')->orderBy('name')->get();

        return view('billing.plans.index', compact('plans'));
    }

    public function create()
    {
        $this->authorize('manage', SubscriptionPlan::class);

        return view('billing.plans.create', [
            'plan' => new SubscriptionPlan(['is_active' => true, 'billing_interval' => SubscriptionPlan::INTERVAL_MONTH]),
            'intervals' => SubscriptionPlan::intervals(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage', SubscriptionPlan::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_interval' => ['required', 'in:month,year'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $data['name'],
            'billing_interval' => $data['billing_interval'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        SubscriptionPlanPrice::create([
            'subscription_plan_id' => $plan->id,
            'amount' => $data['amount'],
            'effective_from' => now()->toDateString(),
        ]);

        return redirect()->route('billing.plans.index')->with('success', 'Paket berhasil ditambahkan.');
    }
}
