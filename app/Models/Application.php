<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use SoftDeletes;

    public const ACCESS_ACTIVE = 'active';
    public const ACCESS_SUSPENDED = 'suspended';
    public const ACCESS_MAINTENANCE = 'maintenance';

    public const HEALTH_UNKNOWN = 'unknown';
    public const HEALTH_UP = 'up';
    public const HEALTH_DOWN = 'down';

    protected $fillable = [
        'gateway_node_id',
        'client_id',
        'name',
        'slug',
        'internal_host',
        'internal_port',
        'access_status',
        'gateway_applied_status',
        'health_status',
        'health_checked_at',
        'health_http_code',
        'health_last_error',
        'deployment_meta',
        'technical_notes',
    ];

    protected $casts = [
        'internal_port' => 'integer',
        'deployment_meta' => 'array',
        'health_checked_at' => 'datetime',
        'health_http_code' => 'integer',
    ];

    public static function healthStatuses()
    {
        return [
            self::HEALTH_UNKNOWN => 'Belum dicek',
            self::HEALTH_UP => 'Backend OK',
            self::HEALTH_DOWN => 'Backend down',
        ];
    }

    /**
     * Path HTTP untuk probe backend (bukan endpoint Gateway Agent /v1/health).
     */
    public function resolvedHealthCheckPath(): string
    {
        $custom = data_get($this->deployment_meta, 'health_check_path');
        if (is_string($custom) && trim($custom) !== '') {
            return '/'.ltrim(trim($custom), '/');
        }

        $path = (string) config('monitoring.health.path', '/');
        $path = '/'.ltrim($path, '/');

        if (strcasecmp($path, '/v1/health') === 0) {
            return '/';
        }

        return $path === '' ? '/' : $path;
    }

    public function healthProbeHostname(): ?string
    {
        if ($this->relationLoaded('primaryDomain') && $this->primaryDomain) {
            return $this->primaryDomain->hostname;
        }

        if ($this->relationLoaded('domains')) {
            $primary = $this->domains->firstWhere('is_primary', true);

            return $primary ? $primary->hostname : null;
        }

        return $this->primaryDomain()->value('hostname');
    }

    public function healthProbeUrl(): string
    {
        return sprintf(
            'http://%s:%d%s',
            $this->internal_host,
            (int) $this->internal_port,
            $this->resolvedHealthCheckPath()
        );
    }

    public static function accessStatuses()
    {
        return [
            self::ACCESS_ACTIVE => 'Aktif',
            self::ACCESS_SUSPENDED => 'Ditangguhkan',
            self::ACCESS_MAINTENANCE => 'Pemeliharaan',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function gatewayNode()
    {
        return $this->belongsTo(GatewayNode::class);
    }

    public function domains()
    {
        return $this->hasMany(ApplicationDomain::class);
    }

    public function primaryDomain()
    {
        return $this->hasOne(ApplicationDomain::class)->where('is_primary', true);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function gatewaySyncLogs()
    {
        return $this->hasMany(GatewaySyncLog::class);
    }

    public function latestGatewaySyncLog()
    {
        return $this->hasOne(GatewaySyncLog::class)->orderByDesc('id');
    }
}
