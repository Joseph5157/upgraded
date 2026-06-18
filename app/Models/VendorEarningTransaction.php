<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEarningTransaction extends Model
{
    /**
     * Transaction type constants.
     */
    public const TYPE_PENDING_ORDER_EARNING = 'pending_order_earning';
    public const TYPE_APPROVE_EARNING       = 'approve_earning';
    public const TYPE_PAYOUT               = 'payout';
    public const TYPE_REVERSAL             = 'reversal';
    public const TYPE_MANUAL_ADJUSTMENT    = 'manual_adjustment';
    public const TYPE_PAYOUT_REVERSAL      = 'payout_reversal';
    public const TYPE_CORRECTION           = 'correction';

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'vendor_id',
        'order_id',
        'vendor_payout_id',
        'type',
        'status',
        'amount_delta',
        'pending_balance_after',
        'approved_balance_after',
        'files_count',
        'rate_per_file',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'amount_delta'           => 'decimal:2',
        'pending_balance_after'  => 'decimal:2',
        'approved_balance_after' => 'decimal:2',
        'rate_per_file'          => 'decimal:2',
        'files_count'            => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendorPayout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
