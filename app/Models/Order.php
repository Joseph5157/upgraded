<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'client_id',
        'token_view',
        'files_count',
        'notes',
        'status',
        'claimed_by',
        'due_at',
        'delivered_at',
        'is_downloaded',
        'source',
        'created_by_user_id',
        'client_link_id',
        'release_count',
        'claimed_at',
    ];

    protected $casts = [
        'due_at'        => 'datetime',
        'delivered_at'  => 'datetime',
        'is_downloaded' => 'boolean',
        'status'        => OrderStatus::class,
    ];

    public function client()      { return $this->belongsTo(Client::class); }
    public function files()       { return $this->hasMany(OrderFile::class); }
    public function report()      { return $this->hasOne(OrderReport::class); }
    public function vendor()      { return $this->belongsTo(User::class, 'claimed_by'); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function link()        { return $this->belongsTo(ClientLink::class, 'client_link_id'); }
    public function orderLogs()   { return $this->hasMany(OrderLog::class); }
    public function refundRequest(){ return $this->hasOne(RefundRequest::class); }

    public function getComputedStatusAttribute()
    {
        if ($this->status === OrderStatus::Delivered)  return 'delivered';
        if ($this->status === OrderStatus::Processing) return 'processing';
        if ($this->due_at && $this->due_at->isPast())  return 'overdue';
        return 'pending';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at
            && $this->status !== OrderStatus::Delivered
            && now()->gt($this->due_at);
    }
}