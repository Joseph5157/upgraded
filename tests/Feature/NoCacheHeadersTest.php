<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_route_sends_no_cache_headers(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/csrf-refresh');

        $response->assertOk();
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }
}
