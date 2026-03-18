<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['name', 'slots', 'slots_consumed', 'price_per_file', 'plan_expiry', 'status'];

    protected $casts = [
        'plan_expiry' => 'datetime',
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
}
