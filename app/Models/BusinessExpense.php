<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessExpense extends Model
{
    /**
     * Expense category constants.
     */
    public const CATEGORY_STAFF_SALARY     = 'staff_salary';
    public const CATEGORY_SOFTWARE         = 'software';
    public const CATEGORY_RAZORPAY_CHARGES = 'razorpay_charges';
    public const CATEGORY_HOSTING          = 'hosting';
    public const CATEGORY_INTERNET         = 'internet';
    public const CATEGORY_DOMAIN           = 'domain';
    public const CATEGORY_OFFICE           = 'office';
    public const CATEGORY_REFUND_LOSS      = 'refund_loss';
    public const CATEGORY_OTHER            = 'other';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_VOIDED = 'voided';

    public static function categories(): array
    {
        return [
            self::CATEGORY_STAFF_SALARY     => 'Staff Salary',
            self::CATEGORY_SOFTWARE         => 'Software',
            self::CATEGORY_RAZORPAY_CHARGES => 'Razorpay Charges',
            self::CATEGORY_HOSTING          => 'Hosting',
            self::CATEGORY_INTERNET         => 'Internet',
            self::CATEGORY_DOMAIN           => 'Domain',
            self::CATEGORY_OFFICE           => 'Office',
            self::CATEGORY_REFUND_LOSS      => 'Refund Loss',
            self::CATEGORY_OTHER            => 'Other',
        ];
    }

    protected $fillable = [
        'category',
        'amount',
        'payment_mode',
        'reference_id',
        'expense_date',
        'created_by',
        'notes',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
        'voided_at'    => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
