<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReport extends Model
{
    protected $fillable = [
        'order_id',
        'ai_report_path',
        'ai_report_original_name',
        'ai_report_disk',
        'plag_report_path',
        'plag_report_original_name',
        'plag_report_disk',
        'ai_skip_reason',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isComplete(): bool
    {
        return (!empty($this->ai_report_path) || !empty($this->ai_skip_reason)) && !empty($this->plag_report_path);
    }
}
