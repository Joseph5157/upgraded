<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPayment extends Model
{
    /**
     * Payment mode constants — use these instead of raw strings.
     */
    public const MODE_UPI           = 'upi';
    public const MODE_BANK_TRANSFER = 'bank_transfer';
    public const MODE_CASH          = 'cash';
    public const MODE_RAZORPAY      = 'razorpay';

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_VOIDED    = 'voided';
    public const STATUS_REFUNDED  = 'refunded';

    protected $fillable = [
        'client_id',
        'amount_received',
        'credits_added',
        'rate_per_credit',
        'payment_mode',
        'transaction_id',
        'received_at',
        'created_by',
        'notes',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'rate_per_credit' => 'decimal:2',
        'credits_added'   => 'integer',
        'received_at'     => 'datetime',
        'voided_at'       => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(ClientCreditTransaction::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
