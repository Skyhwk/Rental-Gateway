<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'invoice_id',
        'amount',
        'paid_at',
        'method',
        'reference_number',
        'proof_path',
        'verification_status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'verified_at' => 'datetime',
    ];

    public static function verificationStatuses()
    {
        return [
            self::STATUS_PENDING => 'Menunggu verifikasi',
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public static function methods()
    {
        return [
            'transfer' => 'Transfer bank',
            'cash' => 'Tunai',
            'other' => 'Lainnya',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
