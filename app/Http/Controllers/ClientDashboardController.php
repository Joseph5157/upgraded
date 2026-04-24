<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Enums\OrderStatus;
use App\Services\CreateClientOrderService;
use App\Services\DeleteClientOrderService;
use App\Services\TelegramService;
use App\Support\LogContext;
use App\Support\StorageLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientDashboardController extends Controller
{
    protected string $storageDisk;

    public function __construct(
        protected CreateClientOrderService $createOrderService,
        protected DeleteClientOrderService $deleteOrderService,
    ) {
        $this->storageDisk = config('filesystems.default', 'r2');
    }

    protected function downloadFromDisk(string $path, string $downloadName, ?string $disk = null)
    {
        $disk = $disk ?: $this->storageDisk;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $stream = Storage::disk($disk)->readStream($path);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $downloadName);
    }
    public function index(Request $request)
    {
        $user = Auth::user();

        $client = $user->client;

        if (!$client) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'link' => 'Your account is not fully configured. Please contact support.',
            ]);
        }

        $ordersQuery = Order::where('client_id', $client->id)
            ->where('source', 'account');

        if ($user->role === 'client') {
            $ordersQuery->where('created_by_user_id', $user->id);
        }

        $orders = $ordersQuery->with(['report', 'files', 'client', 'refundRequest'])
            ->latest()
            ->get();

        if ($this->telegramColumnsReady() && ! $user->telegram_chat_id && ! $user->telegram_link_token) {
            $user->forceFill(['telegram_link_token' => Str::random(48)])->save();
        }

        $telegramBotUsername = config('services.telegram.bot_username');
        $telegramConnectUrl = ($this->telegramColumnsReady() && $telegramBotUsername && $user->telegram_link_token)
            ? 'https://t.me/' . ltrim($telegramBotUsername, '@') . '?start=' . $user->telegram_link_token
            : null;

        $dashboardSignature = $this->buildDashboardSignature($user, $client);
        $consumed = (int) $client->fresh()->slots_consumed;
        $remaining = max(0, (int) $client->total_slots - $consumed);
        $paymentSetting = PaymentSetting::active()->first();

        return view('client.dashboard', compact('client', 'orders', 'dashboardSignature', 'telegramConnectUrl', 'consumed', 'remaining', 'paymentSetting'));
    }

    public function pulse(Request $request)
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'link' => 'Your account is not fully configured. Please contact support.',
            ]);
        }

        $signature = $this->buildDashboardSignature($user, $client);

        if ((string) $request->query('signature', '') === $signature) {
            return response()->json([
                'signature' => $signature,
                'checked_at' => now()->toIso8601String(),
            ]);
        }

        $ordersQuery = Order::where('client_id', $client->id)
            ->where('source', 'account');

        if ($user->role === 'client') {
            $ordersQuery->where('created_by_user_id', $user->id);
        }

        $orders = $ordersQuery->with(['report', 'files', 'client', 'refundRequest'])
            ->latest()
            ->get();
        $consumed = (int) $client->fresh()->slots_consumed;
        $remaining = max(0, (int) $client->total_slots - $consumed);

        return response()->json([
            'signature' => $signature,
            'checked_at' => now()->toIso8601String(),
            'liveHtml' => view('client.dashboard.partials.live', compact('client', 'orders', 'remaining', 'consumed'))->render(),
        ]);
    }

    public function store(Request $request)
    {
        $user   = Auth::user();
        $client = $user->client;

        if (!$client) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'link' => 'Your account is not fully configured. Please contact support.',
            ]);
        }

        $request->validate([
            'files.*' => 'required|file|mimes:pdf,doc,docx,zip|max:102400',
            'files'   => 'required|array|min:1|max:1',
            'notes'   => 'nullable|string|max:1000',
        ]);

        try {
            $order = $this->createOrderService->execute(
                $client,
                $request->file('files'),
                'account',
                [
                    'notes'              => $request->input('notes') ?: null,
                    'created_by_user_id' => $user->id,
                ],
            );
        } catch (\Exception $e) {
            Log::warning('order.create_failed', array_merge(
                LogContext::forUser($user, LogContext::currentRequest()),
                [
                    'source' => 'account',
                    'client_id' => $client->id,
                    'file_count' => is_array($request->file('files')) ? count($request->file('files')) : null,
                    'exception' => class_basename($e),
                    'message' => $e->getMessage(),
                ]
            ));
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('client.dashboard')->with('success', 'Order created successfully. Tracking ID: ' . $order->token_view);
    }

    public function destroyFile(Order $order, OrderFile $file)
    {
        $user = Auth::user();
        $client = $user->client;

        if ($order->client_id !== $client->id || $order->created_by_user_id !== $user->id) {
            abort(403);
        }

        if ($file->order_id !== $order->id) {
            abort(403);
        }

        if (in_array($order->status, [OrderStatus::Claimed, OrderStatus::Processing, OrderStatus::Delivered], true)) {
            abort(403, 'Files cannot be deleted after the order has been reserved, processed, or delivered.');
        }

        StorageLifecycle::deleteStoredFileIfPresent($file->disk ?: $this->storageDisk, $file->file_path);
        $file->delete();

        return back()->with('success', 'File deleted successfully.');
    }

    public function destroy(Order $order)
    {
        $user   = Auth::user();
        $client = $user->client;

        if ($order->client_id !== $client->id || $order->created_by_user_id !== $user->id) {
            abort(403);
        }

        try {
            $slotRestored = $this->deleteOrderService->execute($order, $client);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $slotRestored
            ? 'Order deleted. Your credit slots have been restored.'
            : 'Order and all files permanently deleted.';

        return back()->with('success', $message);
    }

    public function regenerateTelegramLink()
    {
        $user = Auth::user();
        if (! $this->telegramColumnsReady()) {
            return back()->with('error', 'Telegram connection is not ready yet. Please ask admin to run migrations.');
        }

        $user->update([
            'telegram_link_token' => Str::random(48),
            'telegram_chat_id' => null,
            'telegram_connected_at' => null,
        ]);

        Log::info('telegram.link_token.regenerated', LogContext::forUser($user, array_merge(
            LogContext::currentRequest(),
            ['user_id' => $user->id]
        )));

        return back()->with('success', 'Telegram connection link refreshed. Please connect again from the card.');
    }

    public function sendTelegramTest(TelegramService $telegramService)
    {
        $user = Auth::user();
        if (! $this->telegramColumnsReady()) {
            return back()->with('error', 'Telegram connection is not ready yet. Please ask admin to run migrations.');
        }

        if (! $user->telegram_chat_id) {
            return back()->with('error', 'Telegram is not connected yet.');
        }

        $ok = $telegramService->sendMessage(
            (string) $user->telegram_chat_id,
            'Test message: your Telegram connection with the portal is active.'
        );

        if (! $ok) {
            Log::warning('telegram.test_message.failed', LogContext::forUser($user, array_merge(
                LogContext::currentRequest(),
                ['user_id' => $user->id, 'chat_id' => $user->telegram_chat_id]
            )));

            return back()->with('error', 'Unable to send test message. Please reconnect Telegram and try again.');
        }

        Log::info('telegram.test_message.sent', LogContext::forUser($user, array_merge(
            LogContext::currentRequest(),
            ['user_id' => $user->id, 'chat_id' => $user->telegram_chat_id]
        )));

        return back()->with('success', 'Telegram test message sent successfully.');
    }

    protected function buildDashboardSignature($user, Client $client): string
    {
        $ordersQuery = Order::query()
            ->where('client_id', $client->id)
            ->where('source', 'account');

        if ($user->role === 'client') {
            $ordersQuery->where('created_by_user_id', $user->id);
        }

        $orderCount = (clone $ordersQuery)->count();
        $maxOrderUpdatedAt = (clone $ordersQuery)->max('updated_at');
        $deliveredCount = (clone $ordersQuery)->where('status', OrderStatus::Delivered)->count();
        $cancelledCount = (clone $ordersQuery)->where('status', OrderStatus::Cancelled)->count();

        $maxReportUpdatedAt = OrderReport::query()
            ->whereHas('order', function ($query) use ($client, $user) {
                $query->where('client_id', $client->id)
                    ->where('source', 'account');

                if ($user->role === 'client') {
                    $query->where('created_by_user_id', $user->id);
                }
            })
            ->max('updated_at');

        $orderTs = $maxOrderUpdatedAt ? strtotime((string) $maxOrderUpdatedAt) : 0;
        $reportTs = $maxReportUpdatedAt ? strtotime((string) $maxReportUpdatedAt) : 0;

        return sha1($orderCount . '|' . $orderTs . '|' . $reportTs . '|' . $deliveredCount . '|' . $cancelledCount);
    }

    protected function telegramColumnsReady(): bool
    {
        return Schema::hasColumn('users', 'telegram_chat_id')
            && Schema::hasColumn('users', 'telegram_link_token')
            && Schema::hasColumn('users', 'telegram_connected_at');
    }
}
