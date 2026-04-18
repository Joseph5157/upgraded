<?php

namespace Tests\Feature\Middleware;

use Tests\TestCase;

class RequestCorrelationTest extends TestCase
{
    public function test_web_requests_include_request_id_header(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Request-Id');

        $requestId = $response->headers->get('X-Request-Id');

        $this->assertNotNull($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/',
            $requestId
        );
    }
}
