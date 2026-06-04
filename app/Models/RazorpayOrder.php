<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayOrder extends Model
{
    protected $fillable = [
        'name', 'phone', 'plan', 'slots', 'amount',
        'razorpay_order_id', 'razorpay_payment_id',
        'status', 'client_id', 'client_link_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientLink()
    {
        return $this->belongsTo(ClientLink::class);
    }
}
