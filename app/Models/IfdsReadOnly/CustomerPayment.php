<?php

declare(strict_types=1);

namespace App\Models\IfdsReadOnly;

use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    protected $connection = 'ifds_readonly';
    protected $table = 'payments';
    public $timestamps = false;

    protected $casts = [
        'payment_date'   => 'date',
        'amount'         => 'decimal:2',
    ];

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId)->whereNull('deleted_at');
    }
}
