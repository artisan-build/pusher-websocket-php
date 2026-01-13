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

class HandshakeCallbacks
{
    public function __construct(
        public ?Closure $connected = null,
    ) {}
}

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
