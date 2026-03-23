<?php

declare(strict_types=1);

namespace App\Models\CustomerDb;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class CustomerAccount extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $connection = 'customer';
    protected $table = 'customer_accounts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'mobile', 'email', 'name', 'company_name', 'gstin',
        'status', 'ifds_synced', 'ifds_customer_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'ifds_synced'      => 'boolean',
        'ifds_customer_id' => 'integer',
    ];

    // ── JWT Interface ──────────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'typ'      => 'customer',
            'cid'      => $this->id,
            'ifds_cid' => $this->ifds_customer_id, // integer id in fuelflow_db
            'mobile'   => $this->mobile,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function profile()
    {
        return $this->hasOne(CustomerProfile::class, 'customer_id');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function sessions()
    {
        return $this->hasMany(CustomerSession::class, 'customer_id');
    }

    public function notifications()
    {
        return $this->hasMany(BffNotification::class, 'customer_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSyncedToIfds(): bool
    {
        return $this->ifds_synced && $this->ifds_customer_id !== null;
    }
}
