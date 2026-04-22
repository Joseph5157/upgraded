<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\VendorReportStorageException;
use App\Exceptions\WorkflowException;
use App\Models\Order;
use App\Models\User;
use App\Support\LogContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadVendorReportService
{
    protected string $storageDisk;

    public function __construct(
        protected OrderWorkflowService $workflowService,
        string $storageDisk = '',
    ) {
        $this->storageDisk = $storageDisk;
    }

    /**
     * Upload both report PDFs, persist the metadata, and auto-deliver the order.
     *
     * @throws \Throwable  On storage failure or workflow rule violation
     */
    public function execute(Order $order, User $user, ?UploadedFile $aiReport, UploadedFile $plagReport, ?string $aiSkipReason = null): void
    {
        $disk            = $this->storageDisk ?: config('filesystems.default', 'r2');
        $aiPath          = null;
        $plagPath        = null;
        $reportPersisted = false;
        $aiSkipped       = ! empty($aiSkipReason);

        try {
            if ($aiReport) {
                $aiPath = $this->storeReportOrFail($aiReport, 'reports/' . $order->id . '/ai', $disk, 'AI');
            }

            $plagPath = $this->storeReportOrFail($plagReport, 'reports/' . $order->id . '/plag', $disk, 'plagiarism');

            if (empty($aiPath) && ! $aiSkipped) {
                throw new WorkflowException('Failed to process the AI report. Please retry with the PDF or provide a skip reason.');
            }
            if (!$plagPath) {
                throw new VendorReportStorageException('Failed to save the plagiarism report file to storage.');
            }

            $this->workflowService->uploadReport($order, $user, [
                'ai_report_path'   => $aiPath,
                'ai_report_disk'   => $disk,
                'ai_skip_reason'   => $aiSkipReason,
                'plag_report_path' => $plagPath,
                'plag_report_disk' => $disk,
            ]);
            $reportPersisted = true;

            $freshOrder = $order->fresh();

            if (in_array($freshOrder->status, [OrderStatus::Pending, OrderStatus::Claimed])) {
                $this->workflowService->startProcessing($freshOrder, $user);
                $freshOrder->refresh();
            }

            $this->workflowService->deliver($freshOrder, $user);
        } catch (\Throwable $e) {
            if (!$reportPersisted) {
                $this->cleanupFile($disk, $aiPath, 'AI', $order->id);
                $this->cleanupFile($disk, $plagPath, 'plagiarism', $order->id);
            }

            Log::error('Vendor report upload failed.', LogContext::forOrder($order, LogContext::forUser($user, array_merge(LogContext::currentRequest(), [
                'disk' => $disk,
                'ai_skipped' => $aiSkipped,
                'report_persisted' => $reportPersisted,
                'ai_report_path' => $aiPath,
                'plag_report_path' => $plagPath,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception' => $e,
            ]))));

            throw $e;
        }
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^\x00-\x7F]+/', '_', $name);
        $name = preg_replace('/[\s\/\\\\:*?"<>|]+/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        if ($name === '' || $name === '.pdf') {
            $name = 'report_' . now()->timestamp . '.pdf';
        }

        return $name;
    }

    private function storeReportOrFail(UploadedFile $file, string $directory, string $disk, string $label): string
    {
        $storedName = Str::uuid()->toString() . '_' . $this->sanitizeFilename($file->getClientOriginalName());

        try {
            $path = $file->storeAs($directory, $storedName, $disk);
        } catch (\Throwable $e) {
            throw VendorReportStorageException::fromThrowable($e, "Failed to store the {$label} report.");
        }

        if (! is_string($path) || $path === '') {
            throw new VendorReportStorageException("Failed to store the {$label} report.");
        }

        return $path;
    }

    private function cleanupFile(string $disk, ?string $path, string $label, int $orderId): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            Log::warning("Failed to clean up {$label} report after upload failure.", [
                'order_id' => $orderId,
                'path'     => $path,
                'disk'     => $disk,
                'message'  => $e->getMessage(),
            ]);
        }
    }
}
