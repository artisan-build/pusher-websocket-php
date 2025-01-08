<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use RuntimeException;

// import ConnectionManager from './connection/connection_manager';
// import {
//   ChannelAuthorizationOptions,
//   UserAuthenticationOptions
// } from './auth/options';
// import {
//   ChannelAuthorizerGenerator,
//   DeprecatedAuthOptions
// } from './auth/deprecated_channel_authorizer';
// import { AuthTransport, Transport } from './config';
// import * as nacl from 'tweetnacl';

/**
 * @property Transport[] $disabledTransports
 * @property Transport[] $enabledTransports
 */
class Options
{
    /**
     * @param  Transport[]  $disabledTransports
     * @param  Transport[]  $enabledTransports
     */
    public function __construct(
        public ?int $activityTimeout = null,

        // DEPRECATED use channelAuthorization instead
        // public ?DeprecatedAuthOptions $auth = null,
        // DEPRECATED use channelAuthorization instead
        // public ?string $authEndpoint = null,
        // DEPRECATED use channelAuthorization instead
        // public ?AuthTransport $authTransport = null,
        // DEPRECATED use channelAuthorization instead
        // public ?ChannelAuthorizerGenerator $authorizer = null,

        // public ?ChannelAuthorizationOptions $channelAuthorization = null,
        // public ?UserAuthenticationOptions $userAuthentication = null,
        public ?array $channelAuthorization = null,
        public ?array $userAuthentication = null,

        public ?string $cluster = null,
        public ?bool $enableStats = null,
        public ?bool $disableStats = null,
        // @param  Transport[]  $disabledTransports
        public ?array $disabledTransports = null,
        // @param  Transport[]  $enabledTransports
        public ?array $enabledTransports = null,
        public ?bool $forceTLS = null,
        public ?string $httpHost = null,
        public ?string $httpPath = null,
        public ?int $httpPort = null,
        public ?int $httpsPort = null,
        public ?bool $ignoreNullOrigin = null,
        // public ?nacl $nacl = null,
        public ?int $pongTimeout = null,
        public ?string $statsHost = null,
        public mixed $timelineParams = null,
        public ?int $unavailableTimeout = null,
        public ?string $wsHost = null,
        public ?string $wsPath = null,
        public ?int $wsPort = null,
        public ?int $wssPort = null,
    ) {}

    public static function validateOptions(self $options)
    {
        if ($options->cluster === null) {
            throw new RuntimeException('Options object must provide a cluster');
        }
        if ($options->disableStats) {
            (new Logger)->warn(
                'The disableStats option is deprecated in favor of enableStats'
            );
        }
    }
}
