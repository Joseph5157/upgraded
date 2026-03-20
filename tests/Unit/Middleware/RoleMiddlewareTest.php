<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleMiddlewareTest extends TestCase
{
    public function test_unauthorized_roles_cannot_access_files()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/files/some-file', 'GET');

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) ['role' => 'client']);

        $this->expectException(HttpException::class);
        $middleware->handle($request, function () {}, 'admin', 'vendor');
    }
}