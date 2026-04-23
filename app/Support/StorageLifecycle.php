<?php

namespace App\Support;

use RuntimeException;
use Illuminate\Support\Facades\Storage;

class StorageLifecycle
{
    public static function deleteStoredFileIfPresent(string $disk, ?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        $store = Storage::disk($disk);

        if (! $store->exists($path)) {
            return;
        }

        if (! $store->delete($path)) {
            throw new RuntimeException("Failed to delete stored file [{$path}] from disk [{$disk}].");
        }
    }

    public static function deleteStoredDirectory(string $disk, string $directory): void
    {
        if ($directory === '') {
            return;
        }

        Storage::disk($disk)->deleteDirectory($directory);
    }

    public static function uniqueDisks(array $disks, string $fallback = 'r2'): array
    {
        return collect($disks)
            ->merge([$fallback])
            ->filter(fn ($disk) => is_string($disk) && $disk !== '')
            ->unique()
            ->values()
            ->all();
    }
}
