<?php

declare(strict_types=1);

namespace App\Models\IfdsReadOnly;

use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    protected $connection = 'ifds_readonly';
    protected $table = 'customer_order_summary';
    public $timestamps = false;

    protected $casts = [
        'order_date'               => 'date',
        'requested_delivery_date'  => 'date',
        'scheduled_delivery_date'  => 'date',
        'actual_delivery_date'     => 'datetime',
        'delivery_scheduled_date'  => 'date',
        'dispatched_at'            => 'datetime',
        'net_amount'               => 'decimal:2',
        'total_amount'             => 'decimal:2',
        'quantity_ordered'         => 'decimal:3',
    ];

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
