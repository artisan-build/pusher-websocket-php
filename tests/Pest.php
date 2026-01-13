<?php

use ArtisanBuild\Pusher\Tests\TestCase;

use function ArtisanBuild\Pusher\Tests\ensurePusherServerIsRunning;

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
| (Soketi, Reverb, etc). These tests are excluded from `composer test`
| and must be run explicitly with `composer test:integration`.
|
| Configure via environment variables in .env or system:
|   PUSHER_HOST, PUSHER_PORT, PUSHER_APP_ID, PUSHER_APP_KEY, PUSHER_APP_SECRET
|
| Commands:
|   composer test             - Unit/Feature tests only (no server needed)
|   composer test:integration - Integration tests (server required)
|   composer test:all         - All tests
|
*/

uses()
    ->beforeAll(function () {
        if (! ensurePusherServerIsRunning()) {
            throw new RuntimeException(
                "Integration tests require a running Pusher-compatible server.\n".
                "Configure PUSHER_HOST and PUSHER_PORT in .env or environment.\n".
                'See .env for current configuration.'
            );
        }
    })
    ->group('integration')
    ->in('Integration');
