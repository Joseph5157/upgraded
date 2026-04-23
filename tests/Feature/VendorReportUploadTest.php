<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\VendorReportStorageException;
use App\Exceptions\WorkflowException;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\User;
use App\Services\UploadVendorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class VendorReportUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_claim_and_upload_immediately_without_refresh(): void
    {
        [$vendor, $order] = $this->createPendingVendorOrder();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $claimResponse = $this->actingAs($vendor)->post(route('orders.claim', $order), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $claimResponse->assertOk()->assertJsonFragment(['success' => true]);

        $payload = $claimResponse->json();
        $this->assertStringContainsString('upload-modal-' . $order->id, (string) ($payload['rowHtml'] ?? ''));
        $this->assertStringContainsString('submit-btn-' . $order->id, (string) ($payload['rowHtml'] ?? ''));

        $uploadResponse = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI report unavailable for this document',
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $uploadResponse
            ->assertOk()
            ->assertJson([
                'success' => 'Reports uploaded. Order delivered successfully.',
            ]);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertDatabaseHas('order_reports', [
            'order_id' => $order->id,
            'ai_skip_reason' => 'AI report unavailable for this document',
        ]);
    }

    public function test_upload_flow_rejects_state_change_and_cleans_up_files(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $workflow = \Mockery::mock(\App\Services\OrderWorkflowService::class);
        $workflow->shouldReceive('uploadReport')
            ->once()
            ->andReturnUsing(function () use ($order): void {
                $order->update([
                    'status' => OrderStatus::Pending,
                    'claimed_by' => null,
                    'claimed_at' => null,
                ]);

                throw new WorkflowException('This order was released back to the available pool while the upload was in progress.');
            });

        try {
            (new UploadVendorReportService($workflow))->execute(
                $order,
                $vendor,
                null,
                UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
                'AI report unavailable for this document',
            );
            $this->fail('Expected the upload to fail after the simulated state change.');
        } catch (WorkflowException $e) {
            $this->assertSame('This order was released back to the available pool while the upload was in progress.', $e->getMessage());
        }

        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_same_filename_uploads_keep_unique_stored_paths(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $firstOrder = $this->createClaimedOrderForVendor($vendor, 'vendor-upload-filename-a');
        $secondOrder = $this->createClaimedOrderForVendor($vendor, 'vendor-upload-filename-b');

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $firstResponse = $this->actingAs($vendor)->post(route('orders.report', $firstOrder), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI report unavailable for this document',
            'plag_report' => UploadedFile::fake()->create('same-name.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $firstResponse->assertOk();

        $secondResponse = $this->actingAs($vendor)->post(route('orders.report', $secondOrder), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI report unavailable for this document',
            'plag_report' => UploadedFile::fake()->create('same-name.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $secondResponse->assertOk();

        $firstReport = OrderReport::where('order_id', $firstOrder->id)->firstOrFail();
        $secondReport = OrderReport::where('order_id', $secondOrder->id)->firstOrFail();

        $this->assertNotSame($firstReport->plag_report_path, $secondReport->plag_report_path);
        $this->assertTrue(Storage::disk('local')->exists($firstReport->plag_report_path));
        $this->assertTrue(Storage::disk('local')->exists($secondReport->plag_report_path));
    }

    public function test_failed_upload_does_not_leave_partial_database_state(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $workflow = \Mockery::mock(\App\Services\OrderWorkflowService::class);
        $workflow->shouldReceive('uploadReport')
            ->once()
            ->andThrow(new WorkflowException('Unable to finalize upload.'));

        try {
            (new UploadVendorReportService($workflow))->execute(
                $order,
                $vendor,
                null,
                UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
                'AI report unavailable for this document',
            );
            $this->fail('Expected the upload to fail.');
        } catch (WorkflowException $e) {
            $this->assertSame('Unable to finalize upload.', $e->getMessage());
        }

        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $order->refresh();
        $this->assertSame(OrderStatus::Claimed, $order->status);
        $this->assertNotNull($order->claimed_by);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_upload_report_uses_current_order_state_before_submitting(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $order = $this->createClaimedOrderForVendor($vendor, 'vendor-upload-stale-state-test');

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $order->update([
            'status' => OrderStatus::Pending,
            'claimed_by' => null,
            'claimed_at' => null,
        ]);

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI report unavailable for this document',
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'error' => 'This order was released back to the available pool because the claim window expired. You can re-claim it from the Available Queue.',
            ]);

        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_upload_report_returns_domain_error_message_for_non_storage_failures(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $this->mock(UploadVendorReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new WorkflowException("Order must be in 'processing' status before delivery."));
        });

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_report' => UploadedFile::fake()->create('ai-report.pdf', 20, 'application/pdf'),
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'error' => "Order must be in 'processing' status before delivery.",
            ]);
    }

    public function test_upload_report_keeps_storage_failures_as_server_errors(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $this->mock(UploadVendorReportService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new VendorReportStorageException('Unable to write file at location: reports/1/ai/test.pdf'));
        });

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_report' => UploadedFile::fake()->create('ai-report.pdf', 20, 'application/pdf'),
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => 'Report upload failed while saving files to storage. Please try again. If the issue continues, contact admin.',
            ]);
    }

    public function test_upload_report_allows_skipping_ai_report_when_reason_is_provided(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI tool failed for this document',
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => 'Reports uploaded. Order delivered successfully.',
            ]);

        $order->refresh();
        $report = OrderReport::where('order_id', $order->id)->first();

        $this->assertNotNull($report);
        $this->assertNull($report->ai_report_path);
        $this->assertSame('AI tool failed for this document', $report->ai_skip_reason);
        $this->assertNotEmpty($report->plag_report_path);
        $this->assertSame(OrderStatus::Delivered, $order->status);

        Storage::disk('local')->assertExists($report->plag_report_path);
    }

    public function test_upload_report_rejects_ai_file_when_skip_flag_is_enabled(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $response = $this->actingAs($vendor)->post(route('orders.report', $order), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'AI tool failed for this document',
            'ai_report' => UploadedFile::fake()->create('ai-report.pdf', 20, 'application/pdf'),
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ai_report']);
    }

    public function test_admin_upload_credits_claimed_vendor_not_admin(): void
    {
        [$vendor, $order] = $this->createVendorOrder();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'delivered_orders_count' => 0,
            'daily_delivered_count' => 0,
        ]);

        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $response = $this->actingAs($admin)->post(route('orders.report', $order), [
            'ai_skipped' => '1',
            'ai_skip_reason' => 'Admin submitted vendor result',
            'plag_report' => UploadedFile::fake()->create('plag-report.pdf', 20, 'application/pdf'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $vendor->refresh();
        $admin->refresh();
        $order->refresh();

        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertSame(1, $vendor->delivered_orders_count);
        $this->assertSame(1, $vendor->daily_delivered_count);
        $this->assertSame(0, $admin->delivered_orders_count);
        $this->assertSame(0, $admin->daily_delivered_count);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    protected function createVendorOrder(): array
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $client = Client::create([
            'name' => 'Client Upload',
            'email' => 'client-upload@example.com',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'vendor-upload-test',
            'files_count' => 1,
            'status' => 'claimed',
            'claimed_by' => $vendor->id,
            'claimed_at' => now(),
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        return [$vendor, $order];
    }

    /**
     * @return array{0: User, 1: Order}
     */
    protected function createPendingVendorOrder(): array
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $client = Client::create([
            'name' => 'Client Upload Pending',
            'email' => 'client-upload-pending@example.com',
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => 'vendor-upload-pending-test',
            'files_count' => 1,
            'status' => OrderStatus::Pending,
            'claimed_by' => null,
            'claimed_at' => null,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        return [$vendor, $order];
    }

    protected function createClaimedOrderForVendor(User $vendor, string $tokenView): Order
    {
        $client = Client::create([
            'name' => 'Client ' . $tokenView,
            'email' => $tokenView . '@example.com',
        ]);

        return Order::create([
            'client_id' => $client->id,
            'token_view' => $tokenView,
            'files_count' => 1,
            'status' => OrderStatus::Claimed,
            'claimed_by' => $vendor->id,
            'claimed_at' => now(),
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);
    }
}
