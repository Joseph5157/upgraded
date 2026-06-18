<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        // Phase 1 financial snapshot fields
        'credits_consumed',
        'client_rate_per_file',
        'client_amount',
        'vendor_rate_per_file',
        'vendor_amount',
        'gross_profit',
        'financial_locked_at',
        'vendor_submitted_at',
        'vendor_approved_at',
        'vendor_rejected_at',
        'credits_refunded_at',
    ];

    protected $casts = [
        'due_at'               => 'datetime',
        'claimed_at'           => 'datetime',
        'delivered_at'         => 'datetime',
        'is_downloaded'        => 'boolean',
        'status'               => OrderStatus::class,
        // Phase 1 financial snapshot casts
        'credits_consumed'     => 'integer',
        'client_rate_per_file' => 'decimal:2',
        'client_amount'        => 'decimal:2',
        'vendor_rate_per_file' => 'decimal:2',
        'vendor_amount'        => 'decimal:2',
        'gross_profit'         => 'decimal:2',
        'financial_locked_at'  => 'datetime',
        'vendor_submitted_at'  => 'datetime',
        'vendor_approved_at'   => 'datetime',
        'vendor_rejected_at'   => 'datetime',
        'credits_refunded_at'  => 'datetime',
    ];

    public function client()       { return $this->belongsTo(Client::class); }
    public function files()        { return $this->hasMany(OrderFile::class); }
    public function report()       { return $this->hasOne(OrderReport::class); }
    public function vendor()       { return $this->belongsTo(User::class, 'claimed_by'); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function link()         { return $this->belongsTo(ClientLink::class, 'client_link_id'); }
    public function orderLogs()    { return $this->hasMany(OrderLog::class); }
    public function refundRequest(){ return $this->hasOne(RefundRequest::class); }

    // Finance relationships (Phase 1)
    public function creditTransaction() { return $this->hasOne(ClientCreditTransaction::class); }
    public function vendorEarningTransactions() { return $this->hasMany(VendorEarningTransaction::class); }

    public function getComputedStatusAttribute()
    {
        if ($this->status === OrderStatus::Delivered)  return 'delivered';
        if ($this->status === OrderStatus::Cancelled)  return 'cancelled';
        if ($this->status === OrderStatus::Claimed)    return 'claimed';
        if ($this->status === OrderStatus::Processing) return 'processing';
        return 'pending';
    }

    public static function hasColumn(string $column): bool
    {
        return Schema::hasColumn('orders', $column);
    }

}
