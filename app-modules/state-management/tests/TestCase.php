<?php

namespace CorvMC\StateManagement\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup for state management tests
        $this->withoutExceptionHandling();
    }
} 