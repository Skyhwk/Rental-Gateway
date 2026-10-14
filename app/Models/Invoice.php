<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'subscription_id',
        'invoice_number',
        'period_start',
        'period_end',
        'amount',
        'amount_paid',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public static function statuses()
    {
        return [
            self::STATUS_UNPAID => 'Belum dibayar',
            self::STATUS_PARTIAL => 'Sebagian',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_OVERDUE => 'Jatuh tempo',
            self::STATUS_VOID => 'Batal',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceDue()
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }
}
