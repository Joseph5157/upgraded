<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'upi_name',
        'upi_id',
        'qr_code_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function setActive(int $id): void
    {
        static::query()->update(['is_active' => false]);
        static::query()->where('id', $id)->update(['is_active' => true]);
    }
}
