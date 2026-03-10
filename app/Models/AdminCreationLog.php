<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminCreationLog extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'target_user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
