<?php

declare(strict_types=1);

namespace App\Models\IfdsReadOnly;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditSummary extends Model
{
    protected $connection = 'ifds_readonly';
    protected $table = 'customer_credit_summary';
    protected $primaryKey = 'customer_id';
    public $timestamps = false;

    protected $casts = [
        'credit_limit'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'available_credit'    => 'decimal:2',
    ];
}
