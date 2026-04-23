<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_for_supported_roles(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 5005,
            'activated_at' => now(),
            'telegram_chat_id' => '101010101',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Telegram');
    }

    public function test_profile_name_can_be_updated(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'portal_number' => 5006,
            'activated_at' => now(),
            'telegram_chat_id' => '202020202',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
    }

    public function test_profile_delete_route_is_removed(): void
    {
        $user = User::factory()->create([
            'role' => 'vendor',
            'portal_number' => 5007,
            'activated_at' => now(),
            'telegram_chat_id' => '303030303',
        ]);

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertStatus(405);
    }
}
