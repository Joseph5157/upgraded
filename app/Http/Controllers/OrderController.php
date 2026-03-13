<?php

namespace App\Http\Controllers;

use App\Models\ClientLink;
use App\Models\Order;
use App\Models\OrderFile;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function showUpload($token)
    {
        $link = ClientLink::where('token', $token)->where('is_active', true)->with('client')->firstOrFail();
        $client = $link->client;
        $orders = Order::where('client_id', $client->id)->with(['report', 'files'])->latest()->get();
        return view('client.upload', compact('link', 'client', 'orders'));
    }

    public function store(Request $request, $token)
    {
        $link = ClientLink::where('token', $token)->where('is_active', true)->with('client')->firstOrFail();

        $request->validate([
            'files.*' => 'required|file|mimes:pdf,doc,docx,zip|max:102400',
            'files'   => 'required|array|min:1|max:20',
        ]);

        try {
            $tokenView = DB::transaction(function () use ($link, $request) {
                // Lock the client row to prevent concurrent slot over-use
                $client = \App\Models\Client::where('id', $link->client_id)->lockForUpdate()->first();

                $totalOrders = $client->orders()->count();
                if ($client->status === 'suspended' || $totalOrders >= $client->slots) {
                    throw new \Exception('Insufficient credits. You have reached your limit of ' . $client->slots . ' files. Please contact Admin for a refill.');
                }

                $tokenView = Str::random(32);

                $order = Order::create([
                    'client_id'      => $client->id,
                    'token_view'     => $tokenView,
                    'files_count'    => count($request->file('files')),
                    'status'         => OrderStatus::Pending,
                    'due_at'         => now()->addMinutes(config('services.portal.default_sla_minutes', 20)),
                    'source'         => 'link',
                    'client_link_id' => $link->id,
                ]);

                foreach ($request->file('files') as $file) {
                    $path = $file->store('orders/' . $order->id);
                    OrderFile::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                    ]);
                }

                if ($client->orders()->count() >= $client->slots) {
                    $client->update(['status' => 'suspended']);
                }

                return $tokenView;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('client.track', $tokenView);
    }

    public function destroyOrder(Request $request, $token, Order $order)
    {
        $link = ClientLink::where('token', $token)->where('is_active', true)->with('client')->firstOrFail();

        if ($order->client_id !== $link->client->id) {
            abort(403);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->files as $file) {
                Storage::delete($file->file_path);
            }
            Storage::deleteDirectory('orders/' . $order->id);

            if ($order->report) {
                if ($order->report->ai_report_path) {
                    Storage::delete($order->report->ai_report_path);
                }
                if ($order->report->plag_report_path) {
                    Storage::delete($order->report->plag_report_path);
                }
                Storage::deleteDirectory('reports/' . $order->id);
            }

            $order->files()->delete();
            $order->report()->delete();
            $order->delete();
        });

        return redirect()->route('client.upload', $token)->with('success', 'Order deleted successfully.');
    }

    public function track($token_view)
    {
        $order = Order::where('token_view', $token_view)->with(['report', 'client'])->firstOrFail();
        return view('client.track', compact('order'));
    }

    public function download($token_view, Request $request)
    {
        $order = Order::where('token_view', $token_view)->with('report')->firstOrFail();

        if ($order->status !== OrderStatus::Delivered || !$order->report) {
            abort(404);
        }

        $type = $request->query('type', 'ai');

        if ($type === 'plag') {
            if (!$order->report->plag_report_path) abort(404);
            if (!Storage::exists($order->report->plag_report_path)) abort(404);
            return Storage::download($order->report->plag_report_path, 'plagiarism-report-' . $order->id . '.pdf');
        }

        if (!$order->report->ai_report_path) abort(404);
        if (!Storage::exists($order->report->ai_report_path)) abort(404);
        $order->update(['is_downloaded' => true]);
        return Storage::download($order->report->ai_report_path, 'ai-report-' . $order->id . '.pdf');
    }
}
