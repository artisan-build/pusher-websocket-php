<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use React\EventLoop\Loop;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the loop is stopped before each test starts fresh
        Loop::get()->stop();
    }

    protected function tearDown(): void
    {
        // Stop the ReactPHP event loop
        Loop::get()->stop();

        parent::tearDown();
    }
}
