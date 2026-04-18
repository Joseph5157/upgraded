<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LogContext
{
    public static function currentRequest(): array
    {
        $request = request();

        if (! $request instanceof Request) {
            return [];
        }

        return static::fromRequest($request);
    }

    public static function fromRequest(Request $request): array
    {
        $user = $request->user();
        $route = $request->route();

        return Arr::whereNotNull([
            'request_id' => $request->attributes->get('request_id'),
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $route?->getName(),
            'user_id' => $user?->id,
            'role' => $user?->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public static function withResponse(Request $request, int $status, float $durationMs): array
    {
        return array_merge(
            static::fromRequest($request),
            [
                'status' => $status,
                'duration_ms' => round($durationMs, 2),
            ],
        );
    }

    public static function forOrder(Order $order, array $context = []): array
    {
        return array_merge($context, Arr::whereNotNull([
            'order_id' => $order->id,
            'order_status' => $order->status?->value,
            'client_id' => $order->client_id,
            'claimed_by' => $order->claimed_by,
        ]));
    }

    public static function forClient(Client $client, array $context = []): array
    {
        return array_merge($context, Arr::whereNotNull([
            'client_id' => $client->id,
            'client_status' => $client->status,
            'slots' => $client->slots,
            'slots_consumed' => $client->slots_consumed,
        ]));
    }

    public static function forUser(User $user, array $context = []): array
    {
        return array_merge($context, Arr::whereNotNull([
            'user_id' => $user->id,
            'role' => $user->role,
            'account_status' => $user->status,
            'client_id' => $user->client_id,
        ]));
    }

    public static function forTargetUser(User $user, array $context = []): array
    {
        return array_merge($context, Arr::whereNotNull([
            'target_user_id' => $user->id,
            'target_role' => $user->role,
            'target_account_status' => $user->status,
            'target_client_id' => $user->client_id,
        ]));
    }
}
