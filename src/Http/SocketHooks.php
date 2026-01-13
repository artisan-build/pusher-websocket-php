<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

interface SocketHooks
{
    public function getReceiveURL(URLLocation $url, string $session): string;

    public function onHeartbeat(HTTPSocket $socket): void;

    public function sendHeartbeat(HTTPSocket $socket): void;

    public function onFinished(HTTPSocket $socket, int $status): void;
}
