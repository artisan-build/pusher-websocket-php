<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Timeline\Timeline;

class StrategyOptions
{
    public ?bool $encrypted = null;

    public ?bool $failFast = null;

    public ?string $hostNonTLS = null;

    public ?string $hostTLS = null;

    public ?string $httpPath = null;

    public ?bool $ignoreNullOrigin = null;

    public ?string $key = null;

    public ?bool $loop = null;

    public ?Timeline $timeline = null;

    public ?int $timeout = null;

    public ?int $timeoutLimit = null;

    public ?int $ttl = null;

    public ?bool $useTLS = null;
}
