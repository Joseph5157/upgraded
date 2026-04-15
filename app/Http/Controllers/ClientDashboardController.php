<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Enums\OrderStatus;
use App\Services\CreateClientOrderService;
use App\Services\DeleteClientOrderService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    protected function downloadFromDisk(string $path, string $downloadName)
    {
        $stream = Storage::disk($this->storageDisk)->readStream($path);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $downloadName);
    }
    public function index()
    {
        $user = Auth::user();

        $client = $user->client;

        if (!$client) {
            abort(403, 'No client account is linked to your profile. Please contact the administrator.');
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

        return view('client.dashboard', compact('client', 'orders', 'dashboardSignature', 'telegramConnectUrl'));
    }

    public function pulse()
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client account is linked to your profile. Please contact the administrator.');
        }

        return response()->json([
            'signature' => $this->buildDashboardSignature($user, $client),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request)
    {
        $user   = Auth::user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client account is linked to your profile. Please contact the administrator.');
        }

        $request->validate([
            'files.*' => 'required|file|mimes:pdf,doc,docx,zip|max:102400',
            'files'   => 'required|array|min:1|max:20',
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

        if (in_array($order->status, [\App\Enums\OrderStatus::Processing, \App\Enums\OrderStatus::Delivered])) {
            abort(403, 'Files cannot be deleted while the order is being processed or has been delivered.');
        }

        Storage::disk($file->disk ?: $this->storageDisk)->delete($file->file_path);
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

        $slotRestored = $this->deleteOrderService->execute($order, $client);

        $message = $slotRestored
            ? 'Order deleted. Your credit slot has been restored.'
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
            return back()->with('error', 'Unable to send test message. Please reconnect Telegram and try again.');
        }

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

        return sha1($orderTs . '|' . $reportTs . '|' . $deliveredCount . '|' . $cancelledCount);
    }

    protected function telegramColumnsReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $ready = Schema::hasColumn('users', 'telegram_chat_id')
            && Schema::hasColumn('users', 'telegram_link_token')
            && Schema::hasColumn('users', 'telegram_connected_at');

        return $ready;
    }
}
