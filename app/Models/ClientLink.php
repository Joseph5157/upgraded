<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ClientLink extends Model
{
    protected $fillable = [
        'client_id',
        'created_by_user_id',
        'token',
        'is_active',
        'revoked_by_user_id',
        'revoked_at',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'revoked_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'last_used_at'=> 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_link_id');
    }

    public function isRevoked(): bool
    {
        return ! $this->is_active || $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }

    public function creditsUsed(): int
    {
        return (int) $this->orders()->sum('files_count');
    }
}
