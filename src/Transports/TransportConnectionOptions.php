<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use ArtisanBuild\Pusher\Timeline\Timeline;

class TransportConnectionOptions
{
    public function __construct(
        public ?Timeline $timeline = null,
        public ?int $activityTimeout = null,
        public ?bool $useTLS = null,
        public ?string $hostTLS = null,
        public ?string $hostNonTLS = null,
        public ?string $httpPath = null,
    ) {}
}
