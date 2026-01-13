<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArtisanBuild\Pusher\Auth\Options\AuthRequestType;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationCallback;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationHandler;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationRequestParams;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use RuntimeException;

class UserAuthenticator
{
    protected static function composeChannelQuery(
        UserAuthenticationRequestParams $params,
        InternalAuthOptions $authOptions,
    ): string {
        $query = 'socket_id='.urlencode($params->socketId);

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
    ): UserAuthenticationHandler {
        if (! array_key_exists($authOptions->transport, Runtime::getAuthorizers()->toArray())) {
            throw new RuntimeException("'{$authOptions->transport}' is not a recognized auth transport");
        }

        return new UserAuthenticationHandler(
            handler: function (
                UserAuthenticationRequestParams $params,
                UserAuthenticationCallback $callback,
            ) use (
                $authOptions,
            ) {
                $query = self::composeChannelQuery($params, $authOptions);

                Runtime::getAuthorizers()[$authOptions->transport](
                    new Runtime,
                    $query,
                    $authOptions,
                    AuthRequestType::UserAuthentication,
                    $callback
                );
            }
        );
    }
}
