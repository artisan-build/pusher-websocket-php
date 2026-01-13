<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Auth\ChannelAuthorizer;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationHandler;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationOptions;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationHandler;
use ArtisanBuild\Pusher\Auth\UserAuthenticator;

// import {
//   ChannelAuthorizationHandler,
//   UserAuthenticationHandler,
//   ChannelAuthorizationOptions
// } from './auth/options';
// import UserAuthenticator from './auth/user_authenticator';
// import ChannelAuthorizer from './auth/channel_authorizer';
// import { ChannelAuthorizerProxy } from './auth/deprecated_channel_authorizer';
// import Runtime from 'runtime';
// import * as nacl from 'tweetnacl';

// export type AuthTransport = 'ajax' | 'jsonp';
// export type Transport =
//   | 'ws'
//   | 'wss'
//   | 'xhr_streaming'
//   | 'xhr_polling'
//   | 'sockjs';

class Config
{
    protected Defaults $defaults;

    // these are all 'required' config parameters, it's not necessary for the user
    // to set them, but they have configured defaults.
    public int $activityTimeout;

    public bool $enableStats;

    public string $httpHost;

    public string $httpPath;

    public int $httpPort;

    public int $httpsPort;

    public int $pongTimeout;

    public string $statsHost;

    public int $unavailableTimeout;

    public bool $useTLS;

    public string $wsHost;

    public string $wsPath;

    public int $wsPort;

    public int $wssPort;

    public UserAuthenticationHandler $userAuthenticator;

    public ChannelAuthorizationHandler $channelAuthorizer;

    // these are all optional parameters or overrrides. The customer can set these
    // but it's not strictly necessary
    public ?bool $forceTLS = null;

    public ?string $cluster = null;

    // TODO
    // public ?Transport[] $disabledTransports = null;
    // public ?Transport[] $enabledTransports = null;
    public ?array $disabledTransports = null;

    public ?array $enabledTransports = null;

    public ?bool $ignoreNullOrigin = null;

    // public ?nacl $nacl = null;
    public mixed $timelineParams = null;

    // getConfig mainly sets the defaults for the options that are not provided
    public function __construct(Options $opts, Pusher $pusher)
    {
        $this->defaults = new Defaults;

        $this->activityTimeout = $opts->activityTimeout ?? $this->defaults->activityTimeout;
        $this->cluster = $opts->cluster;
        $this->httpPath = $opts->httpPath ?? $this->defaults->httpPath;
        $this->httpPort = $opts->httpPort ?? $this->defaults->httpPort;
        $this->httpsPort = $opts->httpsPort ?? $this->defaults->httpsPort;
        $this->pongTimeout = $opts->pongTimeout ?? $this->defaults->pongTimeout;
        $this->statsHost = $opts->statsHost ?? $this->defaults->stats_host;
        $this->unavailableTimeout = $opts->unavailableTimeout ?? $this->defaults->unavailableTimeout;
        $this->wsPath = $opts->wsPath ?? $this->defaults->wsPath;
        $this->wsPort = $opts->wsPort ?? $this->defaults->wsPort;
        $this->wssPort = $opts->wssPort ?? $this->defaults->wssPort;

        $this->enableStats = $this->getEnableStatsConfig($opts);
        $this->httpHost = $this->getHttpHost($opts);
        $this->useTLS = $this->shouldUseTLS($opts);
        $this->wsHost = $this->getWebsocketHost($opts);

        $this->userAuthenticator = $this->buildUserAuthenticator($opts);
        $this->channelAuthorizer = $this->buildChannelAuthorizer($opts, $pusher);

        // if ('disabledTransports' in opts)
        //   $this->disabledTransports = $opts->disabledTransports;
        // if ('enabledTransports' in opts)
        //   $this->enabledTransports = $opts->enabledTransports;
        // if ('ignoreNullOrigin' in opts)
        //   $this->ignoreNullOrigin = $opts->ignoreNullOrigin;
        // if ('timelineParams' in opts) $this->timelineParams = $opts->timelineParams;
        // if ('nacl' in opts) {
        //   $this->nacl = $opts->nacl;
        // }
    }

    public function getHttpHost(Options $opts): string
    {
        if ($opts->httpHost) {
            return $opts->httpHost;
        }
        if ($opts->cluster) {
            return "sockjs-{$opts->cluster}.pusher.com";
        }

        return $this->defaults->httpHost;
    }

    public function getWebsocketHost(Options $opts): string
    {
        if ($opts->wsHost) {
            return $opts->wsHost;
        }

        return $this->getWebsocketHostFromCluster($opts->cluster);
    }

    public function getWebsocketHostFromCluster(string $cluster): string
    {
        return "ws-{$cluster}.pusher.com";
    }

    public function shouldUseTLS(Options $opts): bool
    {
        if ($opts->forceTLS === false) {
            return false;
        }

        // Default to TLS
        return $opts->forceTLS ?? true;
    }

    // if enableStats is set take the value
    // if disableStats is set take the inverse
    // otherwise default to false
    public function getEnableStatsConfig(Options $opts): bool
    {
        if (! is_null($opts->enableStats)) {
            return $opts->enableStats;
        }
        if (! is_null($opts->disableStats)) {
            return ! $opts->disableStats;
        }

        return false;
    }

    public function buildUserAuthenticator(Options $opts): UserAuthenticationHandler
    {
        $userAuthentication = [
            ...$this->defaults->userAuthentication,
            ...($opts->userAuthentication ?? []),
        ];
        if (
            array_key_exists('customHandler', $userAuthentication) &&
            $userAuthentication['customHandler'] != null
        ) {
            return $userAuthentication['customHandler'];
        }
        $userAuthentication = new InternalAuthOptions(...$userAuthentication);

        return (new UserAuthenticator)($userAuthentication);
    }

    public function buildChannelAuth(Options $opts, Pusher $pusher): ChannelAuthorizationOptions
    {
        $channelAuthorization = [];
        if (! is_null($opts->channelAuthorization)) {
            $channelAuthorization = [
                ...$this->defaults->channelAuthorization,
                ...$opts->channelAuthorization,
            ];
        } else {
            $channelAuthorization = [
                'transport' => $opts->authTransport ?? $this->defaults->authTransport,
                'endpoint' => $opts->authEndpoint ?? $this->defaults->authEndpoint,
            ];
            // if (! is_null($opts->auth)) {
            //     if (array_key_exists('params', $opts->auth)) {
            //         $channelAuthorization['params'] = $opts->auth->params;
            //     }
            //     if (array_key_exists('headers', $opts->auth)) {
            //         $channelAuthorization['headers'] = $opts->auth->headers;
            //     }
            // }
            // if (array_key_exists('authorizer', $opts)) {
            //     $channelAuthorization['customHandler'] = ChannelAuthorizerProxy(
            //         $pusher,
            //         $channelAuthorization,
            //         $opts->authorizer
            //     );
            // }
        }

        return new ChannelAuthorizationOptions(...$channelAuthorization);
    }

    public function buildChannelAuthorizer(
        Options $opts,
        Pusher $pusher
    ): ChannelAuthorizationHandler {
        $channelAuthorization = $this->buildChannelAuth($opts, $pusher);

        if ($channelAuthorization->customHandler != null) {
            return $channelAuthorization->customHandler;
        }

        $channelAuthorization = new InternalAuthOptions(
            $channelAuthorization->transport,
            $channelAuthorization->endpoint,
            $channelAuthorization->params,
            $channelAuthorization->headers,
            $channelAuthorization->paramsProvider,
            $channelAuthorization->headersProvider,
        );

        return (new ChannelAuthorizer)($channelAuthorization);
    }
}
