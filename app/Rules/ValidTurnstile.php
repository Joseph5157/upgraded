<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTurnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verified = app(TurnstileService::class)->verify($value, request()->ip());

        if (! $verified) {
            $fail('Security challenge verification failed. Please try again.');
        }
    }
}
