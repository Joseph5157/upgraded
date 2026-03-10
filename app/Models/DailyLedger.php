<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLedger extends Model
{
    protected $fillable = [
        'date',
        'total_revenue',
        'vendor_payouts',
        'operational_costs',
        'net_profit',
        'client_breakdown',
        'vendor_breakdown',
        'total_orders',
    ];

    protected $casts = [
        'date' => 'date',
        'client_breakdown' => 'array',
        'vendor_breakdown' => 'array',
    ];
}
