<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientOrderServiceTest extends TestCase
{
    /** @test */
    public function test_uploading_three_files_consumes_three_credits(): void
    {
        Storage::fake('r2');

        $client = Client::create([
            'name' => 'Test Client',
            'slots' => 10,
            'slots_consumed' => 0,
            'status' => 'active',
        ]);

        $service = app(\App\Services\CreateClientOrderService::class);

        $order = $service->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
            UploadedFile::fake()->create('c.pdf', 100),
        ], 'account');

        $client->refresh();

        $this->assertEquals(3, $order->files_count);
        $this->assertEquals(3, $client->slots_consumed);
    }

    /** @test */
    public function test_upload_fails_when_selected_files_exceed_remaining_credits(): void
    {
        Storage::fake('r2');

        $client = Client::create([
            'name' => 'Test Client',
            'slots' => 5,
            'slots_consumed' => 4,
            'status' => 'active',
        ]);

        $service = app(\App\Services\CreateClientOrderService::class);

        $this->expectException(\Exception::class);

        $service->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
        ], 'account');
    }

    /** @test */
    public function test_deleting_undelivered_order_restores_file_credits(): void
    {
        $client = Client::create([
            'name' => 'Test Client',
            'slots' => 10,
            'slots_consumed' => 6,
            'status' => 'active',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'abc123',
            'files_count' => 3,
            'status' => \App\Enums\OrderStatus::Pending,
            'source' => 'account',
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);
        $service->execute($order, $client);

        $client->refresh();

        $this->assertEquals(3, $client->slots_consumed);
    }

    /** @test */
    public function test_deleting_delivered_order_does_not_restore_credits(): void
    {
        $client = Client::create([
            'name' => 'Test Client',
            'slots' => 10,
            'slots_consumed' => 6,
            'status' => 'active',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'abc123',
            'files_count' => 3,
            'status' => \App\Enums\OrderStatus::Delivered,
            'source' => 'account',
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);
        $service->execute($order, $client);

        $client->refresh();

        $this->assertEquals(6, $client->slots_consumed);
    }
}