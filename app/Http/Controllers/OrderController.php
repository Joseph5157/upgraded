<?php

namespace App\Http\Controllers;

use App\Models\ClientLink;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Rules\ValidTurnstile;
use App\Services\CreateClientOrderService;
use App\Services\DeleteClientOrderService;
use App\Support\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OrderController extends Controller
{
    protected string $storageDisk;

    public function __construct(
        protected CreateClientOrderService $createOrderService,
        protected DeleteClientOrderService $deleteOrderService,
    ) {
        $this->storageDisk = config('filesystems.default', 'r2');
    }

    protected function downloadFromDisk(string $path, string $downloadName, string $disk)
    {
        $stream = Storage::disk($disk)->readStream($path);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $downloadName, [
            'Content-Type'              => 'application/pdf',
            'Content-Disposition'       => 'attachment; filename="' . $downloadName . '"',
            'X-Content-Type-Options'    => 'nosniff',
        ]);
    }

    protected function bundleReports(Order $order)
    {
        $zipPath = storage_path('app/tmp/order-' . $order->id . '-reports-' . now()->timestamp . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to prepare report bundle.');
        }

        $report = $order->report;

        if ($report->ai_report_path) {
            $aiDisk = $report->ai_report_disk ?: $this->storageDisk;
            if (Storage::disk($aiDisk)->exists($report->ai_report_path)) {
                $zip->addFromString(
                    basename($report->ai_report_path),
                    Storage::disk($aiDisk)->get($report->ai_report_path)
                );
            }
        } elseif ($report->ai_skip_reason) {
            $zip->addFromString(
                'ai-report-note.txt',
                "AI report was not generated for order #{$order->id}.\nReason: {$report->ai_skip_reason}\n"
            );
        }

        if ($report->plag_report_path) {
            $plagDisk = $report->plag_report_disk ?: $this->storageDisk;
            if (Storage::disk($plagDisk)->exists($report->plag_report_path)) {
                $zip->addFromString(
                    basename($report->plag_report_path),
                    Storage::disk($plagDisk)->get($report->plag_report_path)
                );
            }
        }

        $zip->close();

        return response()->download(
            $zipPath,
            'order-' . $order->id . '-reports.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }
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
            'files.*'               => 'required|file|mimes:pdf,doc,docx,zip|max:102400',
            'files'                 => 'required|array|min:1|max:20',
            'cf-turnstile-response' => ['required', 'string', new ValidTurnstile],
        ]);

        try {
            $order = $this->createOrderService->execute(
                $link->client,
                $request->file('files'),
                'link',
                ['client_link_id' => $link->id],
            );
        } catch (\Exception $e) {
            Log::warning('order.create_failed', array_merge(
                LogContext::currentRequest(),
                [
                    'source' => 'link',
                    'client_id' => $link->client_id,
                    'client_link_id' => $link->id,
                    'file_count' => is_array($request->file('files')) ? count($request->file('files')) : null,
                    'exception' => class_basename($e),
                    'message' => $e->getMessage(),
                ]
            ));
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('client.track', $order->token_view);
    }

    public function destroyOrder(Request $request, $token, Order $order)
    {
        $link = ClientLink::where('token', $token)->where('is_active', true)->with('client')->firstOrFail();

        if ($order->client_id !== $link->client->id) {
            abort(403);
        }

        try {
            $this->deleteOrderService->execute($order, $link->client);
        } catch (\Exception $e) {
            return redirect()->route('client.upload', $token)->with('error', $e->getMessage());
        }

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

        $type = $request->query('type');

        // Mark as downloaded on the first successful download of either report type.
        if (!$order->is_downloaded) {
            $order->update(['is_downloaded' => true]);
        }

        if ($type === null) {
            return $this->bundleReports($order);
        }

        if ($type === 'plag') {
            if (!$order->report->plag_report_path) abort(404);
            $disk = $order->report->plag_report_disk ?: $this->storageDisk;
            if (!Storage::disk($disk)->exists($order->report->plag_report_path)) abort(404);
            return $this->downloadFromDisk($order->report->plag_report_path, basename($order->report->plag_report_path), $disk);
        }

        if (!$order->report->ai_report_path) abort(404);
        $disk = $order->report->ai_report_disk ?: $this->storageDisk;
        if (!Storage::disk($disk)->exists($order->report->ai_report_path)) abort(404);
        return $this->downloadFromDisk($order->report->ai_report_path, basename($order->report->ai_report_path), $disk);
    }
}
