<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientLink;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Models\User;
use App\Services\CreateClientOrderService;
use App\Services\PortalTelegramAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeTelegramAlerts(): void
    {
        $this->app->instance(PortalTelegramAlertService::class, new class extends PortalTelegramAlertService {
            public function __construct() {}
            public function notifyOrderAccepted(...$args): void {}
            public function notifyOrderCompleted(...$args): void {}
        });
    }

    protected function makeClient(int $slots = 10, int $consumed = 0): Client
    {
        return Client::create([
            'name'           => 'Guest Client',
            'slots'          => $slots,
            'slots_consumed' => $consumed,
            'status'         => 'active',
        ]);
    }

    protected function makeLink(Client $client, array $overrides = []): ClientLink
    {
        return ClientLink::create(array_merge([
            'client_id' => $client->id,
            'token' => Str::random(40),
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ], $overrides));
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'portal_number' => fake()->unique()->numberBetween(100000, 999999),
            'activated_at' => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'password' => bcrypt('password'),
        ]);
    }

    protected function uploadGuestOrder(Client $client, ClientLink $link, string $filename = 'document.pdf'): Order
    {
        /** @var CreateClientOrderService $service */
        $service = app(CreateClientOrderService::class);

        return $service->execute($client, [
            UploadedFile::fake()->create($filename, 100, 'application/pdf'),
        ], 'link', ['client_link_id' => $link->id]);
    }

    protected function prepareR2Disk(): void
    {
        $root = storage_path('app/testing-disks/r2');
        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }
    }

    protected function mockReportDisk(array $existingPaths, array $contents): void
    {
        $disk = new class($existingPaths, $contents) {
            public function __construct(
                protected array $existingPaths,
                protected array $contents,
            ) {}

            public function exists(string $path): bool
            {
                return in_array($path, $this->existingPaths, true);
            }

            public function get(string $path): string
            {
                return (string) ($this->contents[$path] ?? '');
            }

            public function readStream(string $path)
            {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $this->get($path));
                rewind($stream);

                return $stream;
            }

            public function delete(string $path): bool
            {
                return true;
            }
        };

        Storage::shouldReceive('disk')->with('r2')->andReturn($disk);
    }

    protected function mockLifecycleDisk(array $existingPaths = [], array $contents = []): object
    {
        $disk = new class($existingPaths, $contents) {
            public array $deletedPaths = [];
            public array $deletedDirectories = [];

            public function __construct(
                protected array $existingPaths,
                protected array $contents,
            ) {}

            public function exists(string $path): bool
            {
                return in_array($path, $this->existingPaths, true);
            }

            public function get(string $path): string
            {
                return (string) ($this->contents[$path] ?? '');
            }

            public function readStream(string $path)
            {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $this->get($path));
                rewind($stream);

                return $stream;
            }

            public function delete(string $path): bool
            {
                $this->deletedPaths[] = $path;

                return true;
            }

            public function deleteDirectory(string $path): bool
            {
                $this->deletedDirectories[] = $path;

                return true;
            }

            public function makeDirectory(string $path): bool
            {
                return true;
            }
        };

        Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

        return $disk;
    }

    public function test_one_link_cannot_see_another_links_orders_even_for_same_client(): void
    {
        $this->prepareR2Disk();
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->fakeTelegramAlerts();

        $client = $this->makeClient(slots: 10);
        $linkA = $this->makeLink($client, ['token' => 'link-a']);
        $linkB = $this->makeLink($client, ['token' => 'link-b']);

        $this->uploadGuestOrder($client, $linkA, 'alpha.pdf');
        $this->uploadGuestOrder($client, $linkB, 'beta.pdf');

        $this->get(route('client.upload', 'link-a'))
            ->assertOk()
            ->assertSee('alpha.pdf')
            ->assertDontSee('beta.pdf');

        $this->get(route('client.upload', 'link-b'))
            ->assertOk()
            ->assertSee('beta.pdf')
            ->assertDontSee('alpha.pdf');
    }

    public function test_guest_upload_deducts_one_credit_immediately(): void
    {
        $this->prepareR2Disk();
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->fakeTelegramAlerts();

        $client = $this->makeClient(slots: 3);
        $link = $this->makeLink($client, ['token' => 'upload-link']);

        $this->post(route('client.store', 'upload-link'), [
            'file' => UploadedFile::fake()->create('upload.pdf', 100, 'application/pdf'),
            'notes' => 'Guest upload test',
        ])->assertRedirect(route('client.upload', 'upload-link'));

        $client->refresh();
        $this->assertSame(1, (int) $client->slots_consumed);

        $order = Order::firstOrFail();
        $this->assertSame($link->id, (int) $order->client_link_id);
        $this->assertSame(1, (int) $order->files_count);
        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_guest_upload_is_blocked_when_credits_are_zero(): void
    {
        $this->prepareR2Disk();
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->fakeTelegramAlerts();

        $client = $this->makeClient(slots: 1, consumed: 1);
        $this->makeLink($client, ['token' => 'zero-link']);

        $this->post(route('client.store', 'zero-link'), [
            'file' => UploadedFile::fake()->create('upload.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_view_and_download_still_work_after_credits_are_zero_before_expiry(): void
    {
        $this->fakeTelegramAlerts();

        $client = $this->makeClient(slots: 1);
        $link = $this->makeLink($client, ['token' => 'credit-window']);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'link',
            'client_link_id' => $link->id,
        ]);
        OrderFile::create([
            'order_id' => $order->id,
            'file_path' => 'ready.pdf',
            'disk' => 'r2',
        ]);
        $client->update(['slots_consumed' => 1]);

        OrderReport::create([
            'order_id' => $order->id,
            'ai_report_path' => 'ready-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'ready-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);
        $this->mockReportDisk([
            'ready-ai.pdf',
            'ready-plag.pdf',
        ], [
            'ready-ai.pdf' => 'ai',
            'ready-plag.pdf' => 'plag',
        ]);

        $this->get(route('client.upload', 'credit-window'))
            ->assertOk()
            ->assertSee('No credits remaining');

        $this->get(route('client.link.track', ['credit-window', $order->token_view]))
            ->assertOk()
            ->assertSee('Order #'.$order->id);

        $this->get(route('client.link.download', ['credit-window', $order->token_view]))
            ->assertOk();

        $this->get(route('client.link.download', ['credit-window', $order->token_view]) . '?type=ai')
            ->assertOk();

        $this->get(route('client.link.download', ['credit-window', $order->token_view]) . '?type=plag')
            ->assertOk();
    }

    public function test_guest_access_fails_after_24_hours(): void
    {
        $this->fakeTelegramAlerts();

        $client = $this->makeClient();
        $link = $this->makeLink($client, [
            'token' => 'expired-link',
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('client.upload', 'expired-link'))->assertNotFound();

        $this->post(route('client.store', 'expired-link'), [
            'file' => UploadedFile::fake()->create('upload.pdf', 100, 'application/pdf'),
        ])->assertNotFound();

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Pending,
            'due_at' => now()->addMinutes(20),
            'source' => 'link',
            'client_link_id' => $link->id,
        ]);

        $this->get(route('client.link.track', ['expired-link', $order->token_view]))->assertNotFound();
        $this->get(route('client.link.download', ['expired-link', $order->token_view]))->assertNotFound();
    }

    public function test_guest_link_still_opens_when_new_lifecycle_columns_are_unavailable(): void
    {
        $this->fakeTelegramAlerts();

        Schema::shouldReceive('hasColumn')
            ->andReturnUsing(function (string $table, string $column): bool {
                return ! in_array($column, ['created_by_user_id', 'revoked_by_user_id', 'revoked_at', 'expires_at', 'last_used_at'], true);
            });

        $client = $this->makeClient();
        $link = $this->makeLink($client, [
            'token' => 'legacy-link',
        ]);

        $response = $this->get(route('client.upload', 'legacy-link'));

        $response->assertOk();
        $response->assertSee('Guest link active');

        $link->refresh();
        $this->assertNull($link->last_used_at);
    }

    public function test_admin_guest_link_index_still_renders_when_new_audit_columns_are_unavailable(): void
    {
        $this->fakeTelegramAlerts();

        Schema::shouldReceive('hasColumn')
            ->andReturnUsing(function (string $table, string $column): bool {
                return ! in_array($column, ['created_by_user_id', 'revoked_by_user_id', 'revoked_at', 'expires_at', 'last_used_at'], true);
            });

        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'legacy-admin-link']);

        $this->actingAs($admin)
            ->get(route('admin.client-links.index'))
            ->assertOk()
            ->assertSee('Guest Links')
            ->assertSee('legacy-admin-link');
    }

    public function test_revoked_link_loses_access_immediately(): void
    {
        $this->fakeTelegramAlerts();

        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'revoke-link']);

        $this->actingAs($admin)
            ->post(route('admin.client-links.revoke', $link))
            ->assertSessionHas('success');

        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertNotNull($link->revoked_at);
        $this->assertSame($admin->id, (int) $link->revoked_by_user_id);

        $this->get(route('client.upload', 'revoke-link'))->assertNotFound();

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'client_link.revoked',
            'subject_id' => $link->id,
        ]);
    }

    public function test_guest_link_cannot_delete_orders(): void
    {
        $this->prepareR2Disk();
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);
        $this->fakeTelegramAlerts();

        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'no-delete-link']);
        $order = $this->uploadGuestOrder($client, $link, 'delete-test.pdf');

        $this->delete(route('client.link.track', ['no-delete-link', $order->token_view]))
            ->assertStatus(405);
    }

    public function test_partial_finished_outputs_can_be_downloaded_individually(): void
    {
        $this->fakeTelegramAlerts();

        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'partial-link']);

        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'link',
            'client_link_id' => $link->id,
        ]);
        OrderFile::create([
            'order_id' => $order->id,
            'file_path' => 'partial.pdf',
            'disk' => 'r2',
        ]);
        OrderReport::create([
            'order_id' => $order->id,
            'ai_report_path' => 'partial-ai.pdf',
            'ai_report_disk' => 'r2',
            'ai_skip_reason' => null,
        ]);
        $this->mockReportDisk([
            'partial-ai.pdf',
        ], [
            'partial-ai.pdf' => 'ai',
        ]);

        $this->get(route('client.link.download', ['partial-link', $order->token_view]) . '?type=ai')
            ->assertOk();

        $this->get(route('client.link.download', ['partial-link', $order->token_view]) . '?type=plag')
            ->assertNotFound();
    }

    public function test_bundle_downloads_fail_closed_when_no_report_artifacts_remain(): void
    {
        $this->fakeTelegramAlerts();
        Storage::fake('r2', ['root' => storage_path('app/testing-disks/r2')]);

        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'missing-bundle-link']);

        $guestOrder = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'link',
            'client_link_id' => $link->id,
        ]);

        OrderReport::create([
            'order_id' => $guestOrder->id,
            'ai_report_path' => 'missing-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'missing-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);

        $this->get(route('client.link.download', ['missing-bundle-link', $guestOrder->token_view]))
            ->assertNotFound();

        $guestOrder->refresh();
        $this->assertFalse($guestOrder->is_downloaded);

        $publicOrder = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'account',
        ]);

        OrderReport::create([
            'order_id' => $publicOrder->id,
            'ai_report_path' => 'missing-public-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'missing-public-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);

        $this->get(route('client.download', $publicOrder->token_view))
            ->assertNotFound();

        $publicOrder->refresh();
        $this->assertFalse($publicOrder->is_downloaded);
    }

    public function test_admin_guest_link_delete_removes_report_files_and_rows(): void
    {
        $this->fakeTelegramAlerts();
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'cleanup-link']);
        $order = Order::create([
            'client_id' => $client->id,
            'token_view' => Str::random(32),
            'files_count' => 1,
            'status' => OrderStatus::Delivered,
            'due_at' => now()->addMinutes(20),
            'source' => 'link',
            'client_link_id' => $link->id,
        ]);
        OrderReport::create([
            'order_id' => $order->id,
            'ai_report_path' => 'reports/' . $order->id . '/ai/cleanup-ai.pdf',
            'ai_report_disk' => 'r2',
            'plag_report_path' => 'reports/' . $order->id . '/plag/cleanup-plag.pdf',
            'plag_report_disk' => 'r2',
        ]);
        $disk = $this->mockLifecycleDisk([
            'reports/' . $order->id . '/ai/cleanup-ai.pdf',
            'reports/' . $order->id . '/plag/cleanup-plag.pdf',
        ], [
            'reports/' . $order->id . '/ai/cleanup-ai.pdf' => 'ai',
            'reports/' . $order->id . '/plag/cleanup-plag.pdf' => 'plag',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.client-links.orders.destroy', [$link, $order]))
            ->assertSessionHas('success');

        $this->assertContains('reports/' . $order->id . '/ai/cleanup-ai.pdf', $disk->deletedPaths);
        $this->assertContains('reports/' . $order->id . '/plag/cleanup-plag.pdf', $disk->deletedPaths);
        $this->assertDatabaseMissing('order_reports', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_create_multiple_active_guest_links_for_same_client(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();

        $this->actingAs($admin)
            ->post(route('admin.client-links.store'), ['client_id' => $client->id])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.client-links.store'), ['client_id' => $client->id])
            ->assertSessionHasErrors('client_id');

        $this->assertSame(1, ClientLink::where('client_id', $client->id)->usable()->count());
    }

    public function test_admin_revoke_action_is_logged_and_auditable(): void
    {
        $admin = $this->makeAdmin();
        $client = $this->makeClient();
        $link = $this->makeLink($client, ['token' => 'audit-link']);

        $this->actingAs($admin)
            ->post(route('admin.client-links.revoke', $link))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'client_link.revoked',
            'subject_id' => $link->id,
            'user_id' => $admin->id,
        ]);
    }
}
