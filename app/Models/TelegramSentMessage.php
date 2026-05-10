<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSentMessage extends Model
{
    protected $fillable = ['chat_id', 'message_id', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
