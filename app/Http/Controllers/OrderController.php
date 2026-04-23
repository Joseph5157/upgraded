<?php

namespace App\Http\Controllers;

use App\Models\ClientLink;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Services\CreateClientOrderService;
use App\Services\AuditLogger;
use App\Support\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OrderController extends Controller
{
    protected string $storageDisk;

    public function __construct(
        protected CreateClientOrderService $createOrderService,
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

    protected function reportDownloadName(?string $originalName, string $path): string
    {
        return $originalName ?: basename($path);
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
        $hasRealArtifact = false;

        if ($report->ai_report_path) {
            $aiDisk = $report->ai_report_disk ?: $this->storageDisk;
            if (Storage::disk($aiDisk)->exists($report->ai_report_path)) {
                $zip->addFromString(
                    $this->reportDownloadName($report->ai_report_original_name, $report->ai_report_path),
                    Storage::disk($aiDisk)->get($report->ai_report_path)
                );
                $hasRealArtifact = true;
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
                    $this->reportDownloadName($report->plag_report_original_name, $report->plag_report_path),
                    Storage::disk($plagDisk)->get($report->plag_report_path)
                );
                $hasRealArtifact = true;
            }
        }

        if (! $hasRealArtifact) {
            abort(404, 'No report files are available for this order.');
        }

        $zip->close();

        return response()->download(
            $zipPath,
            'order-' . $order->id . '-reports.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    protected function resolveGuestLinkOrFail(string $token): ClientLink
    {
        $link = ClientLink::where('token', $token)->with('client')->firstOrFail();

        abort_if(! $link->isUsable(), 404);

        return $link;
    }

    protected function guestCreditsRemaining(ClientLink $link): int
    {
        $client = $link->client->fresh();

        return max(0, (int) $client->slots - (int) $client->slots_consumed);
    }

    protected function touchGuestLinkUsage(ClientLink $link): void
    {
        if (! Schema::hasColumn('client_links', 'last_used_at')) {
            return;
        }

        try {
            $link->forceFill(['last_used_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('client_link.touch_failed', array_merge(
                LogContext::currentRequest(),
                [
                    'client_link_id' => $link->id,
                    'exception' => class_basename($e),
                    'message' => $e->getMessage(),
                ]
            ));
        }
    }

    protected function assertGuestOrderScope(ClientLink $link, Order $order): void
    {
        abort_if($order->client_link_id !== $link->id, 404);
        abort_if($order->created_at->lt(now()->subDay()), 404);
    }

    protected function orderHasDownloadableGuestOutput(Order $order, ?string $type = null): bool
    {
        $report = $order->report;

        if (! $report) {
            return false;
        }

        if ($type === 'plag') {
            $disk = $report->plag_report_disk ?: $this->storageDisk;
            return (bool) $report->plag_report_path && Storage::disk($disk)->exists($report->plag_report_path);
        }

        if ($type === 'ai') {
            $disk = $report->ai_report_disk ?: $this->storageDisk;
            return (bool) $report->ai_report_path && Storage::disk($disk)->exists($report->ai_report_path);
        }

        $aiDisk = $report->ai_report_disk ?: $this->storageDisk;
        $plagDisk = $report->plag_report_disk ?: $this->storageDisk;

        return (
            ($report->ai_report_path && Storage::disk($aiDisk)->exists($report->ai_report_path))
            || ($report->plag_report_path && Storage::disk($plagDisk)->exists($report->plag_report_path))
        );
    }

    public function showUpload($token)
    {
        $link = $this->resolveGuestLinkOrFail($token);
        $client = $link->client;
        $orders = Order::where('client_link_id', $link->id)
            ->where('created_at', '>=', now()->subDay())
            ->with(['report', 'files'])
            ->latest()
            ->get();

        $this->touchGuestLinkUsage($link);

        app(AuditLogger::class)->record('client_link.viewed', $link, [
            'client_id' => $client->id,
            'orders_visible' => $orders->count(),
        ]);

        return view('client.upload', compact('link', 'client', 'orders'));
    }

    public function store(Request $request, $token)
    {
        $link = $this->resolveGuestLinkOrFail($token);

        $request->validate([
            'file'                  => 'required|file|mimes:pdf,doc,docx,zip|max:102400',
            'notes'                 => 'nullable|string|max:5000',
        ]);

        $remainingCredits = $this->guestCreditsRemaining($link);

        if ($remainingCredits <= 0) {
            return back()->withErrors([
                'file' => 'This guest link has no credits remaining. Existing orders remain available until the link expires.',
            ]);
        }

        try {
            $order = $this->createOrderService->execute(
                $link->client,
                [$request->file('file')],
                'link',
                [
                    'client_link_id' => $link->id,
                    'notes' => $request->input('notes'),
                ],
            );
        } catch (\Exception $e) {
            Log::warning('order.create_failed', array_merge(
                LogContext::currentRequest(),
                [
                    'source' => 'link',
                    'client_id' => $link->client_id,
                    'client_link_id' => $link->id,
                    'file_count' => 1,
                    'exception' => class_basename($e),
                    'message' => $e->getMessage(),
                ]
            ));
            return back()->with('error', $e->getMessage());
        }

        $this->touchGuestLinkUsage($link);

        app(AuditLogger::class)->record('client_link.uploaded', $order, [
            'client_link_id' => $link->id,
            'credits_used' => 1,
            'remaining_credits' => $this->guestCreditsRemaining($link),
        ]);

        return redirect()->route('client.upload', $token)
            ->with('success', "Order #{$order->id} submitted successfully. Your files are being processed.");

    }

    public function trackGuest($token, Order $order)
    {
        $link = $this->resolveGuestLinkOrFail($token);
        $this->assertGuestOrderScope($link, $order);

        $order->load(['report', 'client']);
        $this->touchGuestLinkUsage($link);
        app(AuditLogger::class)->record('client_link.order_viewed', $order, [
            'client_link_id' => $link->id,
        ]);

        return view('client.track', compact('order', 'link'));
    }

    public function downloadGuest($token, Order $order, Request $request)
    {
        $link = $this->resolveGuestLinkOrFail($token);
        $this->assertGuestOrderScope($link, $order);

        $order->load('report');

        if ($order->status !== OrderStatus::Delivered || ! $order->report) {
            abort(404);
        }

        $type = $request->query('type');

        if (! $this->orderHasDownloadableGuestOutput($order, $type)) {
            abort(404);
        }

        if (! $order->is_downloaded) {
            $order->update(['is_downloaded' => true]);
        }

        $link->forceFill(['last_used_at' => now()])->save();
        app(AuditLogger::class)->record('client_link.downloaded', $order, [
            'client_link_id' => $link->id,
            'download_type' => $type ?: 'bundle',
        ]);

        if ($type === null) {
            return $this->bundleReports($order);
        }

        if ($type === 'plag') {
            if (! $order->report->plag_report_path) abort(404);
            $disk = $order->report->plag_report_disk ?: $this->storageDisk;
            if (!Storage::disk($disk)->exists($order->report->plag_report_path)) abort(404);
            return $this->downloadFromDisk(
                $order->report->plag_report_path,
                $this->reportDownloadName($order->report->plag_report_original_name, $order->report->plag_report_path),
                $disk
            );
        }

        if (! $order->report->ai_report_path) abort(404);
        $disk = $order->report->ai_report_disk ?: $this->storageDisk;
        if (!Storage::disk($disk)->exists($order->report->ai_report_path)) abort(404);
        return $this->downloadFromDisk(
            $order->report->ai_report_path,
            $this->reportDownloadName($order->report->ai_report_original_name, $order->report->ai_report_path),
            $disk
        );
    }

    public function track($token_view)
    {
        $order = Order::where('token_view', $token_view)->with(['report', 'client'])->firstOrFail();
        abort_if($order->client_link_id !== null, 404);
        return view('client.track', compact('order'));
    }

    public function download($token_view, Request $request)
    {
        $order = Order::where('token_view', $token_view)->with('report')->firstOrFail();
        abort_if($order->client_link_id !== null, 404);

        if ($order->status !== OrderStatus::Delivered || !$order->report) {
            abort(404);
        }

        $type = $request->query('type');

        if (! $this->orderHasDownloadableGuestOutput($order, $type)) {
            abort(404);
        }

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
            return $this->downloadFromDisk(
                $order->report->plag_report_path,
                $this->reportDownloadName($order->report->plag_report_original_name, $order->report->plag_report_path),
                $disk
            );
        }

        if (!$order->report->ai_report_path) abort(404);
        $disk = $order->report->ai_report_disk ?: $this->storageDisk;
        if (!Storage::disk($disk)->exists($order->report->ai_report_path)) abort(404);
        return $this->downloadFromDisk(
            $order->report->ai_report_path,
            $this->reportDownloadName($order->report->ai_report_original_name, $order->report->ai_report_path),
            $disk
        );
    }
}
