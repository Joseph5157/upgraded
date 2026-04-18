<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuditLogger
{
    public function record(string $eventType, ?Model $subject = null, array $meta = [], ?int $userId = null): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'request_id' => $this->requestId($request),
            'user_id' => $userId ?? $request?->user()?->id,
            'event_type' => $eventType,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => Arr::whereNotNull($meta),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    protected function requestId(mixed $request): ?string
    {
        if (! $request instanceof Request) {
            return null;
        }

        $requestId = $request->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
