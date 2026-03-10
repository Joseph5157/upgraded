<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'message',
        'target',
        'type',
        'active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dismissals()
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    public function isDismissedBy(User $user): bool
    {
        return $this->dismissals()->where('user_id', $user->id)->exists();
    }

    public function scopeActiveForUser($query, User $user)
    {
        return $query
            ->where('active', true)
            ->where(function ($q) use ($user) {
                $q->where('target', 'all')
                  ->orWhere('target', $user->role);
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->whereDoesntHave('dismissals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }
}