<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

class TransportManagerOptions extends PingDelayOptions
{
    public function __construct(
        ?int $minPingDelay = null,
        ?int $maxPingDelay = null,
        ?int $pingDelay = null,
        public ?int $lives = null,
    ) {
        parent::__construct($minPingDelay, $maxPingDelay, $pingDelay);
    }
}
