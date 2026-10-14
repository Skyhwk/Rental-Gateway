<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    public static function statuses()
    {
        return [
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_INACTIVE => 'Nonaktif',
            self::STATUS_ARCHIVED => 'Arsip',
        ];
    }

    protected $fillable = [
        'company_name',
        'pic_name',
        'email',
        'phone',
        'address',
        'status',
        'internal_notes',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
