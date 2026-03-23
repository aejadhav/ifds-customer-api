<?php

declare(strict_types=1);

namespace App\Models\CustomerDb;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $connection = 'customer';
    protected $table = 'customer_profiles';
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'notify_order_confirmed', 'notify_order_dispatched',
        'notify_out_for_delivery', 'notify_order_delivered',
        'notify_invoice_generated', 'notify_payment_due',
        'notify_credit_80', 'notify_credit_exceeded',
        'preferred_language',
    ];

    protected $casts = [
        'notify_order_confirmed'   => 'boolean',
        'notify_order_dispatched'  => 'boolean',
        'notify_out_for_delivery'  => 'boolean',
        'notify_order_delivered'   => 'boolean',
        'notify_invoice_generated' => 'boolean',
        'notify_payment_due'       => 'boolean',
        'notify_credit_80'         => 'boolean',
        'notify_credit_exceeded'   => 'boolean',
    ];
}
