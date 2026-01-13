<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationHandler;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationRequestParams;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Channels\Channel;
use Closure;

interface DeprecatedChannelAuthorizer
{
    public function authorize(string $socketId, Closure $callback): void;
}
