<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_wrong_role_redirects_to_login_instead_of_403()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/admin/dashboard', 'GET');
        $request->setLaravelSession($this->app['session']->driver());

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) ['role' => 'client']);
        Auth::shouldReceive('logout')->once();

        $response = $middleware->handle($request, function () {}, 'admin', 'vendor');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }

    public function test_correct_role_passes_through()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/admin/dashboard', 'GET');

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) ['role' => 'admin']);

        $called = false;
        $middleware->handle($request, function () use (&$called) {
            $called = true;
            return response('ok');
        }, 'admin');

        $this->assertTrue($called);
    }

    public function test_unauthenticated_user_redirects_to_login()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/admin/dashboard', 'GET');

        Auth::shouldReceive('check')->andReturn(false);

        $response = $middleware->handle($request, function () {}, 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }
}
