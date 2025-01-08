<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

readonly class Defaults
{
    public function __construct(
        public string $VERSION = '7.0.3', // co-pilot
        public int $PROTOCOL = 7,

        public int $wsPort = 80,
        public int $wssPort = 443,
        public string $wsPath = '',
        // DEPRECATED: SockJS fallback parameters
        public string $httpHost = 'sockjs.pusher.com',
        public int $httpPort = 80,
        public int $httpsPort = 443,
        public string $httpPath = '/pusher',
        // DEPRECATED: Stats
        public string $stats_host = 'stats.pusher.com',
        // DEPRECATED: Other settings
        public string $authEndpoint = '/pusher/auth',
        // TODO: make this an enum for safety
        // public AuthTransport $authTransport = 'ajax',
        public string $authTransport = 'ajax',
        public int $activityTimeout = 120_000,
        public int $pongTimeout = 30_000,
        public int $unavailableTimeout = 10_000,
        public array $userAuthentication = [
            'endpoint' => '/pusher/user-auth',
            'transport' => 'ajax',
        ],
        public array $channelAuthorization = [
            'endpoint' => '/pusher/auth',
            'transport' => 'ajax',
        ],

        // CDN configuration
        public ?string $cdn_http = null, // CDN_HTTP,
        public ?string $cdn_https = null, // CDN_HTTPS,
        public ?string $dependency_suffix = null, // DEPENDENCY_SUFFIX
    ) {}
}
