<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnforceSessionTimeout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnforceSessionTimeoutTest extends TestCase
{
    public function test_valid_session_passes_through(): void
    {
        $middleware = new EnforceSessionTimeout();
        $request = Request::create('/dashboard', 'GET');

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) [
            'session_expires_at' => now()->addMinutes(60),
        ]);

        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_expired_session_redirects_to_login(): void
    {
        $middleware = new EnforceSessionTimeout();
        $request = Request::create('/dashboard', 'GET');
        $request->setLaravelSession($this->app['session']->driver());

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) [
            'session_expires_at' => now()->subMinutes(5),
        ]);
        Auth::shouldReceive('logout')->once();

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }

    public function test_null_session_expires_at_passes_through(): void
    {
        $middleware = new EnforceSessionTimeout();
        $request = Request::create('/dashboard', 'GET');

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) [
            'session_expires_at' => null,
        ]);

        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
