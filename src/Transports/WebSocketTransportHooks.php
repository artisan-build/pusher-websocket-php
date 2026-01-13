<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;

/**
 * WebSocket transport hooks using ratchet/pawl.
 */
class WebSocketTransportHooks implements TransportHooks
{
    public function getFile(): ?string
    {
        return null;
    }

    public function getUrls(): URLScheme
    {
        return URLSchemes::ws();
    }

    public function handlesActivityChecks(): bool
    {
        return false;
    }

    public function supportsPing(): bool
    {
        return true;
    }

    public function isInitialized(): bool
    {
        return true;
    }

    public function isSupported(mixed $environment = null): bool
    {
        return true;
    }

    public function getSocket(string $url, mixed $options = null): mixed
    {
        $connector = new Connector(Loop::get());

        // Create a wrapper that will be populated when the connection opens
        $wrapper = new WebSocketWrapper;

        $connector($url)->then(
            function (WebSocket $connection) use ($wrapper) {
                $wrapper->setConnection($connection);
            },
            function (\Throwable $e) use ($wrapper) {
                $wrapper->setError($e);
            }
        );

        return $wrapper;
    }

    public function beforeOpen(mixed $socket, string $path): void
    {
        // No pre-open action needed for WebSockets
    }
}
