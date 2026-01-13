<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

class HTTPPollingSocketHooks implements SocketHooks
{
    public function getReceiveURL(URLLocation $url, string $session): string
    {
        return $url->base.'/'.$session.'/xhr'.$url->queryString;
    }

    public function onHeartbeat(HTTPSocket $socket): void
    {
        // next HTTP request will reset server's activity timer
    }

    public function sendHeartbeat(HTTPSocket $socket): void
    {
        $socket->sendRaw('[]');
    }

    public function onFinished(HTTPSocket $socket, int $status): void
    {
        if ($status === 200) {
            $socket->reconnect();
        } else {
            $socket->onClose(1006, 'Connection interrupted ('.$status.')', false);
        }
    }
}
