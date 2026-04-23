<?php

namespace Tests\Feature;

use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'portal_number' => fake()->unique()->numberBetween(100000, 999999),
            'activated_at' => now(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
        ]);
    }

    public function test_store_and_destroy_payment_setting_keeps_file_and_row_consistent(): void
    {
        $root = storage_path('app/testing-disks/public');
        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }
        config(['filesystems.disks.public.root' => $root]);

        $admin = $this->makeAdmin();
        $qr = UploadedFile::fake()->create('payment.png', 100, 'image/png');

        $this->actingAs($admin)
            ->post(route('admin.payment-settings.store'), [
                'upi_name' => 'Primary UPI',
                'upi_id' => 'primary@upi',
                'qr_code' => $qr,
            ])
            ->assertSessionHas('success');

        $setting = PaymentSetting::firstOrFail();
        $this->assertSame('Primary UPI', $setting->upi_name);
        $this->assertNotNull($setting->qr_code_path);
        Storage::disk('public')->assertExists($setting->qr_code_path);

        $second = PaymentSetting::create([
            'upi_name' => 'Secondary UPI',
            'upi_id' => 'secondary@upi',
            'qr_code_path' => null,
            'is_active' => false,
        ]);
        PaymentSetting::setActive($second->id);

        $this->actingAs($admin)
            ->delete(route('admin.payment-settings.destroy', $setting))
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing($setting->qr_code_path);
        $this->assertDatabaseMissing('payment_settings', ['id' => $setting->id]);
    }

    public function test_update_replaces_qr_code_without_orphaning_old_file(): void
    {
        $root = storage_path('app/testing-disks/public');
        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }
        config(['filesystems.disks.public.root' => $root]);

        $admin = $this->makeAdmin();

        $oldSetting = PaymentSetting::create([
            'upi_name' => 'Primary UPI',
            'upi_id' => 'primary@upi',
            'qr_code_path' => 'qr-codes/old.png',
            'is_active' => false,
        ]);
        Storage::disk('public')->put('qr-codes/old.png', 'old');

        $newQr = UploadedFile::fake()->create('new-payment.png', 100, 'image/png');

        $this->actingAs($admin)
            ->post(route('admin.payment-settings.update', $oldSetting), [
                'upi_name' => 'Updated UPI',
                'upi_id' => 'updated@upi',
                'qr_code' => $newQr,
            ])
            ->assertSessionHas('success');

        $oldSetting->refresh();

        $this->assertSame('Updated UPI', $oldSetting->upi_name);
        $this->assertSame('updated@upi', $oldSetting->upi_id);
        $this->assertNotSame('qr-codes/old.png', $oldSetting->qr_code_path);
        Storage::disk('public')->assertMissing('qr-codes/old.png');
        Storage::disk('public')->assertExists($oldSetting->qr_code_path);
    }

    public function test_destroy_is_idempotent_when_qr_file_is_missing(): void
    {
        $root = storage_path('app/testing-disks/public');
        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }
        config(['filesystems.disks.public.root' => $root]);

        $admin = $this->makeAdmin();
        $setting = PaymentSetting::create([
            'upi_name' => 'Broken QR',
            'upi_id' => 'broken@upi',
            'qr_code_path' => 'qr-codes/missing.png',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.payment-settings.destroy', $setting))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('payment_settings', ['id' => $setting->id]);
        Storage::disk('public')->assertMissing('qr-codes/missing.png');
    }
}
