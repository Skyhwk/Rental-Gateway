<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'client_id',
        'application_id',
        'subscription_plan_id',
        'price_amount',
        'starts_at',
        'ends_at',
        'next_billing_date',
        'grace_days',
        'status',
        'notes',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'next_billing_date' => 'date',
        'grace_days' => 'integer',
    ];

    public static function statuses()
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_PAST_DUE => 'Lewat jatuh tempo',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_EXPIRED => 'Berakhir',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
