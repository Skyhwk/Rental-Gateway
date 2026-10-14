<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    public const INTERVAL_MONTH = 'month';
    public const INTERVAL_YEAR = 'year';

    protected $fillable = [
        'name',
        'billing_interval',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function intervals()
    {
        return [
            self::INTERVAL_MONTH => 'Bulanan',
            self::INTERVAL_YEAR => 'Tahunan',
        ];
    }

    public function prices()
    {
        return $this->hasMany(SubscriptionPlanPrice::class);
    }

    public function getCurrentPriceRecord()
    {
        $today = now()->toDateString();

        return $this->prices()
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
