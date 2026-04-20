<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadVendorReportService
{
    protected string $storageDisk;

    public function __construct(
        protected OrderWorkflowService $workflowService,
        string $storageDisk = '',
    ) {
        $this->storageDisk = $storageDisk ?: config('filesystems.default', 'r2');
    }

    /**
     * Upload both report PDFs, persist the metadata, and auto-deliver the order.
     *
     * @throws \Throwable  On storage failure or workflow rule violation
     */
    public function execute(Order $order, User $user, ?UploadedFile $aiReport, UploadedFile $plagReport, ?string $aiSkipReason = null): void
    {
        $disk            = $this->storageDisk;
        $aiPath          = null;
        $plagPath        = null;
        $reportPersisted = false;

        try {
            if ($aiReport) {
                $aiName = $this->sanitizeFilename($aiReport->getClientOriginalName());
                $aiPath = $aiReport->storeAs('reports/' . $order->id . '/ai', $aiName, $disk);
            }

            try {
                $plagName = $this->sanitizeFilename($plagReport->getClientOriginalName());
                $plagPath = $plagReport->storeAs('reports/' . $order->id . '/plag', $plagName, $disk);
            } catch (\Throwable $e) {
                if ($aiPath) {
                    Storage::disk($disk)->delete($aiPath);
                }
                throw $e;
            }

            if (empty($aiPath) && empty($aiSkipReason)) {
                throw new \Exception('Failed to process AI report file. Please try again or contact support.');
            }
            if (!$plagPath) {
                throw new \Exception('Failed to save the Plagiarism report file to storage. Please try again or contact support.');
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

            Log::error('Vendor report upload failed.', [
                'order_id'  => $order->id,
                'user_id'   => $user->id,
                'disk'      => $disk,
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        }
    }

    private function sanitizeFilename(string $name): string
    {
        // Strip non-ASCII, replace whitespace and dangerous chars with underscores,
        // then collapse repeated underscores and trim.
        $name = preg_replace('/[^\x00-\x7F]+/', '_', $name);
        $name = preg_replace('/[\s\/\\\\:*?"<>|]+/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        // Fall back to a timestamp name if nothing usable remains.
        if ($name === '' || $name === '.pdf') {
            $name = 'report_' . now()->timestamp . '.pdf';
        }

        return $name;
    }

    private function cleanupFile(string $disk, ?string $path, string $label, int $orderId): void
    {
        if (!$path) {
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
