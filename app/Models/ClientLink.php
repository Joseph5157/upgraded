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

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_link_id');
    }
}
