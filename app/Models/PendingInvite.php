<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingInvite extends Model
{
    protected $fillable = [
        'name',
        'role',
        'slots',
        'price_per_file',
        'payout_rate',
        'invite_token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'     => 'datetime',
            'price_per_file' => 'decimal:2',
            'payout_rate'    => 'decimal:2',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
