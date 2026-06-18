<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $attrs = []): Client
    {
        return Client::create(array_merge([
            'name'           => 'Test Client',
            'slots'          => 10,
            'slots_consumed' => 0,
            'credit_balance' => 10,
            'status'         => 'active',
        ], $attrs));
    }

    #[Test]
    public function test_uploading_three_files_consumes_three_credits(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        $client = $this->makeClient(['credit_balance' => 10]);

        $service = app(\App\Services\CreateClientOrderService::class);

        $order = $service->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
            UploadedFile::fake()->create('c.pdf', 100),
        ], 'account');

        $client->refresh();

        $this->assertEquals(3, $order->files_count);
        // New system: credit_balance decremented, NOT slots_consumed
        $this->assertEquals(7, $client->credit_balance);
        // Old slots column must not be touched
        $this->assertEquals(0, $client->slots_consumed);
    }

    #[Test]
    public function test_upload_fails_when_selected_files_exceed_remaining_credits(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        // Only 1 credit — trying to upload 2 files should fail
        $client = $this->makeClient(['credit_balance' => 1]);

        $service = app(\App\Services\CreateClientOrderService::class);

        $this->expectException(\Exception::class);

        $service->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
            UploadedFile::fake()->create('b.pdf', 100),
        ], 'account');
    }

    #[Test]
    public function test_upload_fails_when_credit_balance_is_zero(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        $client = $this->makeClient(['credit_balance' => 0]);

        $service = app(\App\Services\CreateClientOrderService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No upload credits remaining');

        $service->execute($client, [
            UploadedFile::fake()->create('a.pdf', 100),
        ], 'account');
    }

    #[Test]
    public function test_deleting_pre_phase4_order_does_not_touch_credit_balance(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        // Pre-Phase-4 order: created manually, no ORDER_DEBIT transaction exists
        $client = $this->makeClient(['credit_balance' => 5]);

        $order = Order::create([
            'client_id'  => $client->id,
            'token_view' => 'abc123',
            'files_count' => 3,
            'status'     => \App\Enums\OrderStatus::Pending,
            'due_at'     => now(),
            'source'     => 'account',
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);
        $creditsRefunded = $service->execute($order, $client);

        $client->refresh();

        // No debit tx → no refund → credit_balance unchanged
        $this->assertEquals(5, $client->credit_balance);
        $this->assertFalse($creditsRefunded);
    }

    #[Test]
    public function test_deleting_delivered_order_does_not_restore_credits(): void
    {
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        $client = $this->makeClient(['credit_balance' => 5]);

        $order = Order::create([
            'client_id'  => $client->id,
            'token_view' => 'abc123',
            'files_count' => 3,
            'status'     => \App\Enums\OrderStatus::Delivered,
            'due_at'     => now(),
            'source'     => 'account',
        ]);

        $service = app(\App\Services\DeleteClientOrderService::class);
        try {
            $service->execute($order, $client);
            $this->fail('Expected delivered orders to be non-deletable.');
        } catch (\Exception $e) {
            $this->assertSame('Only unclaimed pending orders can be deleted.', $e->getMessage());
        }

        $client->refresh();

        // credit_balance must be untouched
        $this->assertEquals(5, $client->credit_balance);
    }
}
