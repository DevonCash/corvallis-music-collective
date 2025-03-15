<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register module service providers
        $this->app->register(\CorvMC\Sponsorship\Providers\SponsorshipServiceProvider::class);
    }
}
