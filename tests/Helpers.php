<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Tests;

/**
 * Load environment variables from a .env file.
 */
function loadEnv(?string $path = null): void
{
    $path ??= dirname(__DIR__).'/.env';

    if (! file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parse KEY=value
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Don't override existing env vars
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }
}

/**
 * Get an environment variable with a default fallback.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}

/**
 * Check if a Pusher-compatible WebSocket server is running.
 */
function ensurePusherServerIsRunning(
    string $host = '127.0.0.1',
    int $port = 6001,
    int $timeout = 1
): bool {
    $host = (string) env('PUSHER_HOST', $host);
    $port = (int) env('PUSHER_PORT', $port);

    set_error_handler(fn () => true);
    $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
    restore_error_handler();

    if ($socket) {
        fclose($socket);

        return true;
    }

    return false;
}

/**
 * Get the WebSocket URL for testing.
 */
function getWebSocketUrl(): string
{
    $host = env('PUSHER_HOST', '127.0.0.1');
    $port = env('PUSHER_PORT', '6001');
    $scheme = env('PUSHER_SCHEME', 'ws');

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
        'app_id' => (string) env('PUSHER_APP_ID', 'app-id'),
        'key' => (string) env('PUSHER_APP_KEY', 'app-key'),
        'secret' => (string) env('PUSHER_APP_SECRET', 'app-secret'),
        'cluster' => (string) env('PUSHER_CLUSTER', 'mt1'),
    ];
}

// Load .env file when this file is included
loadEnv();
