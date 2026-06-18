<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reference_id',
        'paid_at',
        'notes',
        // Phase 1 additions
        'payment_mode',
        'paid_by',
        'status',
        // Phase 10B void columns
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'paid_at'   => 'datetime',
        'amount'    => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function earningTransactions()
    {
        return $this->hasMany(VendorEarningTransaction::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
