<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReport extends Model
{
    protected $fillable = [
        'order_id',
        'ai_report_path',
        'plag_report_path',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isComplete(): bool
    {
        return !empty($this->ai_report_path) && !empty($this->plag_report_path);
    }
}