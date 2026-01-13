<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

class URLSchemeParams
{
    public function __construct(
        public bool $useTLS,
        public string $hostTLS,
        public string $hostNonTLS,
        public string $httpPath,
    ) {}
}
