<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'slots',
        'slots_consumed',
        'price_per_file',
        'plan_expiry',
        'status',
        // Phase 1 finance fields
        'credit_balance',
        'credits_migrated_at',
    ];

    protected $casts = [
        'plan_expiry'          => 'datetime',
        'credits_migrated_at'  => 'datetime',
        'credit_balance'       => 'integer',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function links()
    {
        return $this->hasMany(ClientLink::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function topupRequests()
    {
        return $this->hasMany(TopupRequest::class);
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function getTotalSlotsAttribute(): int
    {
        return (int) $this->slots;
    }

    // Finance relationships (Phase 1)
    public function clientPayments()
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(ClientCreditTransaction::class);
    }
}
