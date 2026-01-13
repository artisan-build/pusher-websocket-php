<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use ArtisanBuild\Pusher\Strategies\Strategy;
use ArtisanBuild\Pusher\Strategies\StrategyOptions;
use ArtisanBuild\Pusher\Timeline\Timeline;
use Closure;

class ConnectionManagerOptions
{
    public function __construct(
        public Timeline $timeline,
        public Closure $getStrategy,
        public int $unavailableTimeout,
        public int $pongTimeout,
        public int $activityTimeout,
        public bool $useTLS,
    ) {}
}
