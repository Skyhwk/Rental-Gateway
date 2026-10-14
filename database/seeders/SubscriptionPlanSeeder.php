<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Bulanan',
                'billing_interval' => SubscriptionPlan::INTERVAL_MONTH,
                'amount' => 150000,
                'description' => 'Paket dasar — tagihan per bulan.',
            ],
            [
                'name' => 'Pro Bulanan',
                'billing_interval' => SubscriptionPlan::INTERVAL_MONTH,
                'amount' => 350000,
                'description' => 'Paket standar dengan dukungan prioritas.',
            ],
            [
                'name' => 'Enterprise Tahunan',
                'billing_interval' => SubscriptionPlan::INTERVAL_YEAR,
                'amount' => 3600000,
                'description' => 'Kontrak tahunan — diskon vs bulanan.',
            ],
        ];

        foreach ($plans as $row) {
            $plan = SubscriptionPlan::firstOrCreate(
                ['name' => $row['name']],
                [
                    'billing_interval' => $row['billing_interval'],
                    'description' => $row['description'],
                    'is_active' => true,
                ]
            );

            if (! $plan->getCurrentPriceRecord()) {
                SubscriptionPlanPrice::create([
                    'subscription_plan_id' => $plan->id,
                    'amount' => $row['amount'],
                    'effective_from' => now()->toDateString(),
                ]);
            }
        }
    }
}
