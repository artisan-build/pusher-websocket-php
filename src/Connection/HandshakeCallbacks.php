<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use Closure;

class HandshakeCallbacks
{
    public function __construct(
        public ?Closure $connected = null,
    ) {}
}
