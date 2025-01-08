<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

use Closure;

class ChannelAuthorizationHandler
{
    public function __construct(
        public Closure $handler,
    ) {}

    public function __invoke(
        ChannelAuthorizationRequestParams $params,
        ChannelAuthorizationCallback $callback,
    ): void {
        ($this->handler)($params, $callback);
    }
}
