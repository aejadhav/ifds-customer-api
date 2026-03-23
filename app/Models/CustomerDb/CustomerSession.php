<?php

declare(strict_types=1);

namespace App\Models\CustomerDb;

use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    protected $connection = 'customer';
    protected $table = 'customer_sessions';
    public $incrementing = false;
    protected $keyType = 'string';
    const UPDATED_AT = null;

    protected $fillable = [
        'id', 'customer_id', 'token_hash', 'device_info',
        'ip_address', 'last_active_at', 'expires_at',
    ];

    protected $casts = [
        'device_info'    => 'array',
        'last_active_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];
}
