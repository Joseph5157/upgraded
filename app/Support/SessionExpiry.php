<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class SessionExpiry
{
    public static function nextMidnight(?CarbonImmutable $reference = null): CarbonImmutable
    {
        $reference ??= CarbonImmutable::now(config('app.timezone'));

        return $reference->startOfDay()->addDay();
    }

    public static function minutesUntilMidnight(?CarbonImmutable $reference = null): int
    {
        $reference ??= CarbonImmutable::now(config('app.timezone'));
        $expiresAt = static::nextMidnight($reference);
        $seconds = $reference->diffInSeconds($expiresAt, false);

        return max(1, (int) ceil($seconds / 60));
    }

    public static function isExpired(?CarbonImmutable $expiresAt, ?CarbonImmutable $reference = null): bool
    {
        if (! $expiresAt) {
            return false;
        }

        $reference ??= CarbonImmutable::now(config('app.timezone'));

        return $reference->greaterThanOrEqualTo($expiresAt);
    }
}
