<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayConfigVersion extends Model
{
    protected $fillable = [
        'application_id',
        'checksum',
        'config_snapshot_path',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
