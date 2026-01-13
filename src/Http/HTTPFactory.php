<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

interface HTTPFactory
{
    public function createStreamingSocket(string $url): HTTPSocket;

    public function createPollingSocket(string $url): HTTPSocket;

    public function createSocket(SocketHooks $hooks, string $url): HTTPSocket;

    public function createXHR(string $method, string $url): HTTPRequest;

    public function createXDR(string $method, string $url): ?HTTPRequest;

    public function createRequest(RequestHooks $hooks, string $method, string $url): HTTPRequest;
}
