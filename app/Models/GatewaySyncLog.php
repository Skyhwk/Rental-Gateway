<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewaySyncLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'application_id',
        'idempotency_key',
        'desired_status',
        'sync_status',
        'error_message',
        'gateway_config_version_id',
        'requested_by',
        'source',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function configVersion()
    {
        return $this->belongsTo(GatewayConfigVersion::class, 'gateway_config_version_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
