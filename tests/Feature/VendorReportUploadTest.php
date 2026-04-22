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
}
