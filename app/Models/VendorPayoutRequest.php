<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayoutRequest extends Model
{
    protected $fillable = [
        'user_id',
        'amount_requested',
        'status',
        'notes',
        'fulfilled_at',
    ];

    protected $casts = [
        'amount_requested' => 'decimal:2',
        'fulfilled_at'     => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
