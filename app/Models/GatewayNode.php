<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayNode extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public static function defaultNode()
    {
        return static::where('is_default', true)->first()
            ?: static::query()->first();
    }
}
