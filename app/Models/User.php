<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'telegram_chat_id',
        'telegram_link_token',
        'telegram_connected_at',
        'password',
        'email_verified_at',
        'role',
        'delivered_orders_count',
        'daily_delivered_count',
        'client_id',
        'status',
        'frozen_at',
        'frozen_reason',
        'is_super_admin',
        'admin_created_by',
        'admin_creation_token',
        'admin_token_expires_at',
        'last_login_at',
        'last_login_ip',
        'session_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'frozen_at'              => 'datetime',
            'password'               => 'hashed',
            'is_super_admin'         => 'boolean',
            'admin_token_expires_at' => 'datetime',
            'last_login_at'          => 'datetime',
            'telegram_connected_at'  => 'datetime',
            'session_expires_at'     => 'datetime',
        ];
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function canCreateAdmins(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canCreateVendors(): bool
    {
        return in_array($this->role, ['admin']);
    }

    public function canCreateClients(): bool
    {
        return in_array($this->role, ['admin']);
    }

    public function requiresEmailVerification(): bool
    {
        return in_array($this->role, ['admin', 'vendor'], true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFrozen($query)
    {
        return $query->where('status', 'frozen');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'claimed_by');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'admin_created_by');
    }

    public function adminCreationLogs()
    {
        return $this->hasMany(AdminCreationLog::class, 'created_by_user_id');
    }
}
