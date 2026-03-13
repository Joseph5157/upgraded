<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Enums\OrderStatus;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientDashboardController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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

        return view('client.dashboard', compact('client', 'orders'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
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
            $orderId = null;
            $tokenView = DB::transaction(function () use ($client, $request, $user, &$orderId) {
                // Lock the client row to prevent race conditions on slot checks
                $client = Client::where('id', $client->id)->lockForUpdate()->first();

                if ($client->plan_expiry && $client->plan_expiry->isPast()) {
                    throw new \Exception('Your plan has expired on ' . $client->plan_expiry->format('d M Y') . '. Please contact Admin to renew.');
                }

                if ($client->status === 'suspended' || $client->slots_consumed >= $client->slots) {
                    throw new \Exception('Insufficient credits. You have reached your limit of ' . $client->slots . ' files. Please contact Admin for a refill.');
                }

                $tokenView = Str::random(32);

                $order = Order::create([
                    'client_id'          => $client->id,
                    'token_view'         => $tokenView,
                    'files_count'        => count($request->file('files')),
                    'notes'              => $request->input('notes') ?: null,
                    'status'             => OrderStatus::Pending,
                    'due_at'             => now()->addMinutes(config('services.portal.default_sla_minutes', 20)),
                    'source'             => 'account',
                    'created_by_user_id' => $user->id,
                ]);

                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->storeAs('orders/' . $order->id, $originalName);
                    OrderFile::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                    ]);
                }

                // Increment permanent consumed counter — never decremented
                $client->increment('slots_consumed');

                // Suspend if consumed all slots
                if ($client->slots_consumed >= $client->slots) {
                    $client->update(['status' => 'suspended']);
                }

                // Store order ID for notification after transaction
                $orderId = $order->id;

                return $tokenView;
            });

            // Send Telegram notification to vendors (after transaction commits)
            if ($orderId) {
                $order = Order::find($orderId);
                $this->notificationService->notifyVendorsNewOrder($order);
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('client.dashboard')->with('success', 'Order created successfully. Tracking ID: ' . $tokenView);
    }

    public function cancel(Order $order)
    {
        $user = Auth::user();
        $client = $user->client;

        if ($order->client_id !== $client->id || $order->created_by_user_id !== $user->id) {
            abort(403);
        }

        // Block cancellation only if the order is claimed AND the SLA has NOT expired.
        // If the deadline has passed (vendor missed SLA), the client is allowed to force-cancel.
        if ($order->claimed_by !== null && !$order->is_overdue) {
            return back()->with('error', 'This order has already been claimed by an agent and cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $client) {
            $order->update([
                'status'     => OrderStatus::Cancelled,
                'claimed_by' => null,
                'claimed_at' => null,
            ]);

            // Delete all files from disk permanently
            foreach ($order->files as $file) {
                Storage::delete($file->file_path);
            }

            // Delete the entire order folder
            Storage::deleteDirectory('orders/' . $order->id);

            // Delete file records from database
            $order->files()->delete();

            // On cancel: decrement consumed because service was never rendered
            $client->decrement('slots_consumed');
            if ($client->status === 'suspended' && $client->fresh()->slots_consumed < $client->slots) {
                $client->update(['status' => 'active']);
            }
        });

        return back()->with('success', 'Order cancelled. Your credit slot has been refunded.');
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

        Storage::delete($file->file_path);
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

        // Capture delivered status BEFORE deletion
        $wasDelivered = $order->status === \App\Enums\OrderStatus::Delivered;

        DB::transaction(function () use ($order, $client, $wasDelivered) {
            // Delete uploaded files from disk
            foreach ($order->files as $file) {
                Storage::delete($file->file_path);
            }
            Storage::deleteDirectory('orders/' . $order->id);

            // Delete report PDFs from disk (AI + Plag)
            if ($order->report) {
                if ($order->report->ai_report_path) {
                    Storage::delete($order->report->ai_report_path);
                }
                if ($order->report->plag_report_path) {
                    Storage::delete($order->report->plag_report_path);
                }
                Storage::deleteDirectory('reports/' . $order->id);
            }

            // Delete DB records
            $order->files()->delete();
            $order->report()->delete();
            $order->delete();

            // Only restore slot if order was NOT delivered (service not rendered)
            if (!$wasDelivered) {
                $client->decrement('slots_consumed');
                if ($client->status === 'suspended' && $client->fresh()->slots_consumed < $client->slots) {
                    $client->update(['status' => 'active']);
                }
            }
        });

        $message = $wasDelivered
            ? 'Order and all files permanently deleted.'
            : 'Order deleted. Your credit slot has been restored.';

        return back()->with('success', $message);
    }
}
