<?php

use ArtisanBuild\Pusher\Tests\TestCase;

use function ArtisanBuild\Pusher\Tests\ensureReverbIsRunning;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Integration Tests - Require Reverb Server
|--------------------------------------------------------------------------
|
| Integration tests require a running Reverb (or Pusher-compatible) server.
| If the server is not running, these tests will be skipped with a helpful
| message. Start Reverb with: php artisan reverb:start
|
*/

uses()
    ->beforeEach(function () {
        if (! ensureReverbIsRunning()) {
            $this->markTestSkipped(
                'Reverb server not running. Start with: php artisan reverb:start'
            );
        }
    })
    ->in('Integration');
