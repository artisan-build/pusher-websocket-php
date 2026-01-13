<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Tests;

use ArtisanBuild\Pusher\Options;
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use React\EventLoop\Loop;

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

// Register the test auth transport (no HTTP, just signing)
Runtime::registerAuthTransport('test', new TestAuth);

/**
 * Create a Pusher client configured for testing.
 *
 * @param  bool  $withAuth  Enable auth using the 'test' transport (no HTTP, just signing)
 */
function createTestClient(bool $withAuth = false): Pusher
{
    $credentials = getTestCredentials();
    $url = getWebSocketUrl();

    $options = new Options(
        cluster: $credentials['cluster'],
        wsHost: parse_url($url, PHP_URL_HOST),
        wsPort: (int) parse_url($url, PHP_URL_PORT),
        forceTLS: parse_url($url, PHP_URL_SCHEME) === 'wss',
    );

    if ($withAuth) {
        // Use the 'test' transport which generates valid signatures directly
        // without needing an HTTP auth server
        $options->channelAuthorization = [
            'transport' => 'test',
        ];
    }

    return new Pusher($credentials['key'], $options);
}

/**
 * Run the ReactPHP event loop for a specified duration or until condition is met.
 *
 * @param  float  $timeout  Maximum time to run (seconds)
 * @param  callable|null  $until  Optional condition callback - stops when returns true
 * @param  float  $checkInterval  How often to check the condition (seconds)
 */
function runEventLoop(float $timeout = 1.0, ?callable $until = null, float $checkInterval = 0.01): void
{
    $loop = Loop::get();

    // Always set up max timeout
    $loop->addTimer($timeout, function () use ($loop) {
        $loop->stop();
    });

    // If we have a condition, check it periodically
    if ($until !== null) {
        /** @var \React\EventLoop\TimerInterface|null $timer */
        $timer = null;
        $timer = $loop->addPeriodicTimer($checkInterval, function () use ($loop, $until, &$timer) {
            if ($until()) {
                if ($timer !== null) {
                    $loop->cancelTimer($timer);
                }
                $loop->stop();
            }
        });
    }

    $loop->run();
}

/**
 * Trigger an event from the server side using the Pusher HTTP API.
 *
 * This is used to test that clients can receive events.
 */
function triggerServerEvent(string $channel, string $event, array $data): bool
{
    $credentials = getTestCredentials();
    $host = env('PUSHER_HOST', '127.0.0.1');
    $port = env('PUSHER_PORT', '6001');

    $body = json_encode([
        'name' => $event,
        'channel' => $channel,
        'data' => json_encode($data),
    ]);

    $path = "/apps/{$credentials['app_id']}/events";
    $method = 'POST';
    $timestamp = time();

    // Build signature string (Pusher HTTP API auth)
    $stringToSign = implode("\n", [
        $method,
        $path,
        implode('&', [
            'auth_key='.$credentials['key'],
            'auth_timestamp='.$timestamp,
            'auth_version=1.0',
            'body_md5='.md5($body),
        ]),
    ]);

    $signature = hash_hmac('sha256', $stringToSign, $credentials['secret']);

    $url = "http://{$host}:{$port}{$path}?".http_build_query([
        'auth_key' => $credentials['key'],
        'auth_timestamp' => $timestamp,
        'auth_version' => '1.0',
        'body_md5' => md5($body),
        'auth_signature' => $signature,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 5,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);

    return $result !== false;
}
