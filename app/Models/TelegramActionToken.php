<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TelegramActionToken extends Model
{
    /**
     * Status constants
     */
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_USED    = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    /**
     * Action type constants — dot-separated hierarchy.
     */
    public const ACTION_ORDER_VIEW                    = 'order.view';
    public const ACTION_ORDER_CANCEL_REQUEST          = 'order.cancel.request';
    public const ACTION_ORDER_CANCEL_CONFIRM          = 'order.cancel.confirm';
    public const ACTION_PAYMENT_APPROVE_REQUEST       = 'payment.approve.request';
    public const ACTION_PAYMENT_APPROVE_CONFIRM       = 'payment.approve.confirm';
    public const ACTION_PAYMENT_REJECT_REQUEST        = 'payment.reject.request';
    public const ACTION_VENDOR_ASSIGNMENT_ACCEPT      = 'vendor.assignment.accept';
    public const ACTION_VENDOR_ASSIGNMENT_REJECT      = 'vendor.assignment.reject';
    public const ACTION_VENDOR_REPORT_APPROVE_REQUEST = 'vendor.report.approve.request';
    public const ACTION_VENDOR_REPORT_APPROVE_CONFIRM = 'vendor.report.approve.confirm';
    public const ACTION_VENDOR_REPORT_FAIL_REQUEST    = 'vendor.report.fail.request';
    public const ACTION_VENDOR_REPORT_REWORK_REQUEST  = 'vendor.report.rework.request';
    public const ACTION_VENDOR_PAYOUT_PAID_REQUEST    = 'vendor.payout.mark_paid.request';
    public const ACTION_VENDOR_PAYOUT_PAID_CONFIRM    = 'vendor.payout.mark_paid.confirm';
    public const ACTION_SUPPORT_ISSUE_CREATE          = 'support.issue.create';

    protected $fillable = [
        'token',
        'created_for_user_id',
        'telegram_user_id',
        'action_type',
        'subject_type',
        'subject_id',
        'payload',
        'required_role',
        'expires_at',
        'used_at',
        'used_by_user_id',
        'status',
    ];

    protected $casts = [
        'payload'    => 'array',
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function createdForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_for_user_id');
    }

    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast()
            || $this->status === self::STATUS_EXPIRED;
    }

    public function isUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }
}
