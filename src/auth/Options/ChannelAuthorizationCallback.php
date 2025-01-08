<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

use Closure;
use Exception;

class ChannelAuthorizationCallback implements AuthTransportCallback
{
    public function __construct(
        public Closure $callback,
    ) {}

    public function __invoke(?Exception $error, ChannelAuthorizationData $data)
    {
        return ($this->callback)($error, $data);
    }
}
