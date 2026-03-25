<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestR2Connection extends Command
{
    protected $signature = 'storage:test-r2';
    protected $description = 'Test R2 bucket connection - write, read, list, delete';

    public function handle(): int
    {
        $this->info('--- R2 Connection Test ---');
        $this->line('FILESYSTEM_DISK : ' . env('FILESYSTEM_DISK'));
        $this->line('R2_BUCKET       : ' . env('R2_BUCKET'));
        $this->line('R2_ENDPOINT     : ' . env('R2_ENDPOINT'));
        $this->line('R2_ACCESS_KEY_ID: ' . substr(env('R2_ACCESS_KEY_ID', ''), 0, 6) . '...');
        $this->newLine();

        $path    = 'r2-test/connection-test.txt';
        $content = 'R2 connection OK - ' . now();
        $passed  = 0;

        // Step 1: Write
        $this->line('1. Writing test file...');
        try {
            Storage::disk('r2')->put($path, $content);
            $this->info('   ✓ Write passed');
            $passed++;
        } catch (\Throwable $e) {
            $this->error('   ✗ Write FAILED: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Step 2: Read back
        $this->line('2. Reading test file...');
        try {
            $read = Storage::disk('r2')->get($path);
            if ($read === $content) {
                $this->info('   ✓ Read passed — content matches');
                $passed++;
            } else {
                $this->warn('   ⚠ Read returned unexpected content: ' . $read);
            }
        } catch (\Throwable $e) {
            $this->error('   ✗ Read FAILED: ' . $e->getMessage());
        }

        // Step 3: List
        $this->line('3. Listing r2-test/ directory...');
        try {
            $files = Storage::disk('r2')->files('r2-test');
            $this->info('   ✓ Files found: ' . implode(', ', $files));
            $passed++;
        } catch (\Throwable $e) {
            $this->error('   ✗ List FAILED: ' . $e->getMessage());
        }

        // Step 4: Delete
        $this->line('4. Deleting test file...');
        try {
            Storage::disk('r2')->delete($path);
            $this->info('   ✓ Delete passed');
            $passed++;
        } catch (\Throwable $e) {
            $this->error('   ✗ Delete FAILED: ' . $e->getMessage());
        }

        $this->newLine();
        if ($passed === 4) {
            $this->info('✅ ALL PASSED — R2 is connected and working correctly.');
            return Command::SUCCESS;
        } else {
            $this->warn("⚠ {$passed}/4 steps passed. Check errors above.");
            return Command::FAILURE;
        }
    }
}
