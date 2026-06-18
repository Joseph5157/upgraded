<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCreditTransaction extends Model
{
    /**
     * Transaction type constants — use these instead of raw strings.
     */
    public const TYPE_OPENING_BALANCE   = 'opening_balance';
    public const TYPE_PAYMENT_CREDIT    = 'payment_credit';
    public const TYPE_ORDER_DEBIT       = 'order_debit';
    public const TYPE_REFUND_CREDIT     = 'refund_credit';
    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const TYPE_CORRECTION        = 'correction';

    protected $fillable = [
        'client_id',
        'order_id',
        'client_payment_id',
        'type',
        'credits_delta',
        'balance_after',
        'rate_per_credit',
        'money_value',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'credits_delta'   => 'integer',
        'balance_after'   => 'integer',
        'rate_per_credit' => 'decimal:2',
        'money_value'     => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function clientPayment(): BelongsTo
    {
        return $this->belongsTo(ClientPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
