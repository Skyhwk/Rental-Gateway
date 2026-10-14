<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDomain extends Model
{
    protected $fillable = [
        'application_id',
        'hostname',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
