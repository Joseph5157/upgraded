<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable audit log for every Telegram action processed by the webhook.
 */
class TelegramEventLog extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_DENIED  = 'denied';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ERROR   = 'error';

    protected $fillable = [
        'telegram_user_id',
        'user_id',
        'event_type',
        'subject_type',
        'subject_id',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
