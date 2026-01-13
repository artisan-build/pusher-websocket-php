<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

class HTTPStreamingSocketHooks implements SocketHooks
{
    public function getReceiveURL(URLLocation $url, string $session): string
    {
        return $url->base.'/'.$session.'/xhr_streaming'.$url->queryString;
    }

    public function onHeartbeat(HTTPSocket $socket): void
    {
        $socket->sendRaw('[]');
    }

    public function sendHeartbeat(HTTPSocket $socket): void
    {
        $socket->sendRaw('[]');
    }

    public function onFinished(HTTPSocket $socket, int $status): void
    {
        $socket->onClose(1006, 'Connection interrupted ('.$status.')', false);
    }
}
