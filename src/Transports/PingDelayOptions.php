<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

class PingDelayOptions
{
    public function __construct(
        public ?int $minPingDelay = null,
        public ?int $maxPingDelay = null,
        public ?int $pingDelay = null,
    ) {}
}
