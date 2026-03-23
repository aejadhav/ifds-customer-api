<?php

declare(strict_types=1);

namespace App\Models\IfdsReadOnly;

use Illuminate\Database\Eloquent\Model;

class CustomerInvoice extends Model
{
    protected $connection = 'ifds_readonly';
    protected $table = 'invoices';
    public $timestamps = false;

    protected $casts = [
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'total_amount'   => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'paid_amount'    => 'decimal:2',
    ];

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId)->whereNull('deleted_at');
    }
}
