<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use Closure;

class ConnectionCallbacks
{
    public function __construct(
        public ?Closure $message = null,
        public ?Closure $ping = null,
        public ?Closure $activity = null,
        public ?Closure $error = null,
        public ?Closure $closed = null,
    ) {}
}
