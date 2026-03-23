<?php

declare(strict_types=1);

namespace App\Models\CustomerDb;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $connection = 'customer';
    protected $table = 'customer_addresses';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'customer_id', 'name', 'address', 'landmark',
        'contact_person', 'contact_mobile', 'is_default', 'ifds_location_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
