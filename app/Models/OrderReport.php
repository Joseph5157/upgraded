<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReport extends Model
{
    protected $fillable = ['order_id', 'report_path'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
