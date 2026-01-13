<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Tests;

use Throwable;

/**
 * Check if a Reverb/Pusher-compatible WebSocket server is running.
 *
 * @param  string  $host  The host to check (default: 127.0.0.1)
 * @param  int  $port  The port to check (default: 8080)
 * @param  int  $timeout  Connection timeout in seconds (default: 1)
 */
function ensureReverbIsRunning(
    string $host = '127.0.0.1',
    int $port = 8080,
    int $timeout = 1
): bool {
    try {
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($socket) {
            fclose($socket);

            return true;
        }
    } catch (Throwable) {
    }

    return false;
}

/**
 * Get the Reverb WebSocket URL for testing.
 */
function getReverbUrl(): string
{
    $host = env('REVERB_HOST', '127.0.0.1');
    $port = env('REVERB_PORT', 8080);
    $scheme = env('REVERB_SCHEME', 'ws');

    return "{$scheme}://{$host}:{$port}";
}

/**
 * Get test Pusher credentials from environment.
 *
 * @return array{app_id: string, key: string, secret: string, cluster: string}
 */
function getTestCredentials(): array
{
    return [
        'app_id' => env('REVERB_APP_ID', 'app-id'),
        'key' => env('REVERB_APP_KEY', 'app-key'),
        'secret' => env('REVERB_APP_SECRET', 'app-secret'),
        'cluster' => env('REVERB_CLUSTER', 'mt1'),
    ];
}
