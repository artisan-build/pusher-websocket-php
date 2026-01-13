<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArtisanBuild\Pusher\Channels\Channel;
use Closure;

/**
 * @phpstan-type ChannelAuthorizerGeneratorFn Closure(Channel, DeprecatedAuthorizerOptions): DeprecatedChannelAuthorizer
 */
interface ChannelAuthorizerGenerator
{
    public function __invoke(Channel $channel, DeprecatedAuthorizerOptions $options): DeprecatedChannelAuthorizer;
}
