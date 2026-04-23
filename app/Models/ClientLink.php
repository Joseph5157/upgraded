<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

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

    protected static array $columnPresence = [];

    protected static function hasColumn(string $column): bool
    {
        return static::$columnPresence[$column]
            ??= Schema::hasColumn('client_links', $column);
    }

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
        return ! $this->is_active
            || (self::hasColumn('revoked_at') && $this->revoked_at !== null);
    }

    public function isExpired(): bool
    {
        if (self::hasColumn('expires_at') && $this->expires_at !== null) {
            return $this->expires_at->isPast();
        }

        if ($this->created_at !== null) {
            return $this->created_at->copy()->addDay()->isPast();
        }

        return false;
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function scopeUsable(Builder $query): Builder
    {
        $query->where('is_active', true);

        if (self::hasColumn('revoked_at')) {
            $query->whereNull('revoked_at');
        }

        if (self::hasColumn('expires_at')) {
            $query->where(function (Builder $builder): void {
                $builder->where(function (Builder $expires): void {
                    $expires->whereNotNull('expires_at')
                        ->where('expires_at', '>', now());
                })->orWhere(function (Builder $fallback): void {
                    $fallback->whereNull('expires_at')
                        ->where('created_at', '>', now()->subDay());
                });
            });
        } else {
            $query->where('created_at', '>', now()->subDay());
        }

        return $query;
    }

    public function creditsUsed(): int
    {
        return (int) $this->orders()->sum('files_count');
    }
}
