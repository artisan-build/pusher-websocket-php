<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

interface TransportHooks
{
    public function getFile(): ?string;

    public function getUrls(): URLScheme;

    public function handlesActivityChecks(): bool;

    public function supportsPing(): bool;

    public function isInitialized(): bool;

    public function isSupported(mixed $environment = null): bool;

    public function getSocket(string $url, mixed $options = null): mixed;

    public function beforeOpen(mixed $socket, string $path): void;
}
