<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

use Closure;

class UserAuthenticationHandler
{
    public function __construct(
        public Closure $handler,
    ) {}

    public function __invoke(
        UserAuthenticationRequestParams $params,
        UserAuthenticationCallback $callback,
    ): void {
        ($this->handler)($params, $callback);
    }
}
