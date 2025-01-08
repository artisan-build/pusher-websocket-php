<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Runtimes\Auth;

use ArtisanBuild\Pusher\Auth\AuthTransport;
use ArtisanBuild\Pusher\Auth\Options\AuthRequestType;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use Closure;

class HttpAuth implements AuthTransport
{
    public function __invoke(
        Runtime $context,
        string $query,
        InternalAuthOptions $authOptions,
        AuthRequestType $authRequestType,
        Closure $callback,
    ): void {
        throw new \RuntimeException('Not implemented');
        // ...
    }
}
