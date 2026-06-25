<?php

namespace App\Services\Telegram;

use App\Models\TelegramActionToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TelegramActionTokenService
{
    /**
     * Create a new action token bound to a subject and optional Telegram user.
     *
     * @param  string      $actionType       One of TelegramActionToken::ACTION_* constants
     * @param  Model|null  $subject          Polymorphic subject (Order, ClientPayment, etc.)
     * @param  array       $payload          Extra data required to execute the action
     * @param  int|null    $createdForUserId Portal user the notification is for
     * @param  string|null $telegramUserId   Telegram user ID who may redeem this token
     * @param  string|null $requiredRole     Portal role required to redeem ('admin','vendor','client')
     * @param  int|null    $ttlMinutes       Override TTL; defaults to config('telegram.action_token_ttl_minutes')
     */
    public function create(
        string $actionType,
        ?Model $subject = null,
        array $payload = [],
        ?int $createdForUserId = null,
        ?string $telegramUserId = null,
        ?string $requiredRole = null,
        ?int $ttlMinutes = null,
    ): TelegramActionToken {
        $ttl = $ttlMinutes ?? config('telegram.action_token_ttl_minutes', 30);

        return TelegramActionToken::create([
            'token'               => (string) Str::uuid(),
            'created_for_user_id' => $createdForUserId,
            'telegram_user_id'    => $telegramUserId,
            'action_type'         => $actionType,
            'subject_type'        => $subject ? get_class($subject) : null,
            'subject_id'          => $subject?->getKey(),
            'payload'             => $payload ?: null,
            'required_role'       => $requiredRole,
            'expires_at'          => now()->addMinutes($ttl),
            'status'              => TelegramActionToken::STATUS_ACTIVE,
        ]);
    }

    /**
     * Validate an incoming UUID token string.
     *
     * Returns the token record if it is active and not expired.
     * Returns null if the token is unknown, expired, used, or revoked.
     */
    public function validate(string $tokenUuid): ?TelegramActionToken
    {
        $token = TelegramActionToken::where('token', $tokenUuid)->first();

        if (! $token) {
            return null;
        }

        if (! $token->isActive()) {
            return null;
        }

        return $token;
    }

    /**
     * Mark a token as used inside a DB transaction.
     *
     * Must be called within the same transaction as the action it guards.
     *
     * @param  TelegramActionToken  $token
     * @param  int|null             $usedByUserId  Portal user who redeemed it
     */
    public function markUsed(TelegramActionToken $token, ?int $usedByUserId = null): void
    {
        $token->update([
            'status'          => TelegramActionToken::STATUS_USED,
            'used_at'         => now(),
            'used_by_user_id' => $usedByUserId,
        ]);
    }

    /**
     * Revoke all active tokens for a given subject and action type prefix.
     *
     * Useful when subject state changes make existing tokens stale,
     * e.g. a payment is manually approved in the portal — revoke any
     * pending Telegram approval tokens for that payment.
     *
     * @param  Model  $subject
     * @param  string $actionTypePrefix  Prefix to match, e.g. 'payment.approve'
     */
    public function revokeForSubject(Model $subject, string $actionTypePrefix): int
    {
        return TelegramActionToken::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey())
            ->where('action_type', 'like', $actionTypePrefix . '%')
            ->where('status', TelegramActionToken::STATUS_ACTIVE)
            ->update(['status' => TelegramActionToken::STATUS_REVOKED]);
    }

    /**
     * Expire all tokens whose expires_at has passed but are still marked active.
     *
     * Called by a scheduled command or prune job.
     */
    public function pruneExpired(): int
    {
        return TelegramActionToken::where('status', TelegramActionToken::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => TelegramActionToken::STATUS_EXPIRED]);
    }

    /**
     * Build the short callback_data string for embedding in Telegram keyboard buttons.
     *
     * Format: "a:<uuid>" — "a:" prefix keeps it short and distinguishable.
     */
    public function callbackData(TelegramActionToken $token): string
    {
        return 'a:' . $token->token;
    }

    /**
     * Parse a callback_data string back to a UUID.
     *
     * Returns null if the format is unrecognised.
     */
    public function parseCallbackData(string $callbackData): ?string
    {
        if (str_starts_with($callbackData, 'a:')) {
            return substr($callbackData, 2);
        }

        return null;
    }
}
