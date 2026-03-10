<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientLink extends Model
{
    protected $fillable = ['client_id', 'token', 'is_active'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
