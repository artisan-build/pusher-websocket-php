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
| Integration Tests - Require Pusher-Compatible Server
|--------------------------------------------------------------------------
|
| Integration tests require a running Pusher-compatible WebSocket server
| (Soketi, Reverb, etc). If no server is available, tests will be skipped.
|
| Configure via environment variables:
|   PUSHER_HOST, PUSHER_PORT, PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET
|
*/

uses()
    ->beforeEach(function () {
        if (! ensureReverbIsRunning()) {
            $this->markTestSkipped(
                'No Pusher-compatible server running. Set PUSHER_HOST and PUSHER_PORT env vars.'
            );
        }
    })
    ->in('Integration');
