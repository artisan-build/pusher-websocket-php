<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArtisanBuild\Pusher\Auth\Options\AuthRequestType;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationHandler;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationRequestParams;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use RuntimeException;

class ChannelAuthorizer
{
    protected static function composeChannelQuery(
        ChannelAuthorizationRequestParams $params,
        InternalAuthOptions $authOptions,
    ): string {
        $query = 'socket_id='.urlencode($params->socketId);

        $query .= '&channel_name='.urlencode($params->channelName);

        foreach ($authOptions->params as $key => $value) {
            $query .= '&'.urlencode($key).'='.urlencode($value);
        }

        if ($authOptions->paramsProvider !== null) {
            $dynamicParams = ($authOptions->paramsProvider)();
            foreach ($dynamicParams as $key => $value) {
                $query .= '&'.urlencode($key).'='.urlencode($value);
            }
        }

        return $query;
    }

    public function __invoke(
        InternalAuthOptions $authOptions,
    ): ChannelAuthorizationHandler {
        if (! array_key_exists($authOptions->transport, Runtime::getAuthorizers()->toArray())) {
            throw new RuntimeException("'{$authOptions->transport}' is not a recognized auth transport");
        }

        return new ChannelAuthorizationHandler(
            handler: function (
                ChannelAuthorizationRequestParams $params,
                ChannelAuthorizationCallback $callback,
            ) use (
                $authOptions,
            ) {
                $query = self::composeChannelQuery($params, $authOptions);

                Runtime::getAuthorizers()[$authOptions->transport](
                    new Runtime,
                    $query,
                    $authOptions,
                    AuthRequestType::ChannelAuthorization,
                    $callback
                );
            }
        );
    }
}
