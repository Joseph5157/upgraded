<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFile extends Model
{
    protected $fillable = ['order_id', 'file_path', 'disk', 'original_name'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
