<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorDailySnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'orders_delivered',
        'amount_earned',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_earned' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
