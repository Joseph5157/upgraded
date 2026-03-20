<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckAccountStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

class CheckAccountStatusTest extends TestCase
{
    public function test_suspended_clients_cannot_upload_files()
    {
        $redirectResponse = new RedirectResponse('/login', 302);
        $redirector = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['route'])
            ->getMock();
        $redirector->method('route')->willReturn($redirectResponse);

        $middleware = new CheckAccountStatus($redirector);
        $request = Request::create('/upload/some-file', 'POST');
        $request->setMethod('POST');
        $sessionMock = $this->getMockBuilder(\Illuminate\Session\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['invalidate', 'regenerateToken'])
            ->getMock();
        $sessionMock->method('invalidate')->willReturn(null);
        $sessionMock->method('regenerateToken')->willReturn(null);
        $request->setLaravelSession($sessionMock);
        $redirectResponse->setSession($sessionMock);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isFrozen'])
            ->getMock();
        $user->status = 'suspended';
        $user->method('isFrozen')->willReturn(true);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('logout')->andReturnNull();

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->getTargetUrl());
    }
}