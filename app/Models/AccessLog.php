<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_APPLICATION_FORM = 'application.form';

    protected $fillable = [
        'application_id',
        'user_id',
        'from_status',
        'to_status',
        'reason',
        'source',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
