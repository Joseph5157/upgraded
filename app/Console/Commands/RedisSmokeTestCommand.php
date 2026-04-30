<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

class RedisSmokeTestCommand extends Command
{
    protected $signature = 'app:redis-smoke-test';
    protected $description = 'Run basic Redis operational checks for cache, sessions, and queues';

    public function handle(): int
    {
        try {
            Redis::connection()->ping();
            $this->info('Redis ping: OK');
        } catch (\Throwable $e) {
            $this->error('Redis ping: FAILED - '.$e->getMessage());

            return Command::FAILURE;
        }

        try {
            Cache::put('redis_smoke_test', 'ok', 60);
            $value = Cache::get('redis_smoke_test');

            if ($value !== 'ok') {
                throw new RuntimeException('Redis cache read/write failed.');
            }

            Cache::forget('redis_smoke_test');
            $this->info('Redis cache write/read: OK');
        } catch (\Throwable $e) {
            $this->error('Redis cache write/read: FAILED - '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->line('Queue connection: '.config('queue.default'));
        $this->line('Session driver: '.config('session.driver'));

        try {
            $pendingJobs = Queue::connection('redis')->size(env('REDIS_QUEUE', 'default'));
            $this->line('Pending jobs: '.$pendingJobs);
        } catch (\Throwable $e) {
            $this->warn('Pending jobs: unavailable - '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
