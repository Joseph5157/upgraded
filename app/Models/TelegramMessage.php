<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Tracks Telegram messages sent by the bot so they can be edited later
 * (e.g. after a payment is approved, edit the pending-approval message).
 */
class TelegramMessage extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'chat_id',
        'message_id',
        'message_type',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
