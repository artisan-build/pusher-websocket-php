<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use Closure;

class ErrorCallbacks
{
    public function __construct(
        public ?Closure $tls_only = null,
        public ?Closure $refused = null,
        public ?Closure $backoff = null,
        public ?Closure $retry = null,
    ) {}
}
