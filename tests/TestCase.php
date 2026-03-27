<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['view.compiled' => storage_path('tmp_views')]);
        config(['services.turnstile.secret' => null]);
    }
}
