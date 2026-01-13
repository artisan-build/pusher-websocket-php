<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationHandler;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationRequestParams;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Pusher;
use Closure;

class ChannelAuthorizerProxy
{
    public static function create(
        Pusher $pusher,
        InternalAuthOptions $authOptions,
        Closure $channelAuthorizerGenerator
    ): Closure {
        $deprecatedAuthorizerOptions = new DeprecatedAuthorizerOptions(
            authTransport: $authOptions->transport,
            authEndpoint: $authOptions->endpoint,
            auth: new DeprecatedAuthOptions(
                params: $authOptions->params,
                headers: $authOptions->headers,
            ),
        );

        return function (
            ChannelAuthorizationRequestParams $params,
            Closure $callback
        ) use ($pusher, $deprecatedAuthorizerOptions, $channelAuthorizerGenerator) {
            $channel = $pusher->channel($params->channelName);
            // This creates a new channel authorizer every time.
            // In the past, this was only done once per channel and reused.
            /** @var DeprecatedChannelAuthorizer $channelAuthorizer */
            $channelAuthorizer = $channelAuthorizerGenerator(
                $channel,
                $deprecatedAuthorizerOptions
            );
            $channelAuthorizer->authorize($params->socketId, $callback);
        };
    }
}
