<?php

declare(strict_types=1);

namespace App\Models\CustomerDb;

use Illuminate\Database\Eloquent\Model;

class BffNotification extends Model
{
    protected $connection = 'customer';
    protected $table = 'bff_notifications';
    public $incrementing = false;
    protected $keyType = 'string';
    const UPDATED_AT = null;

    protected $fillable = [
        'id', 'customer_id', 'type', 'title', 'body', 'data', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];
}
