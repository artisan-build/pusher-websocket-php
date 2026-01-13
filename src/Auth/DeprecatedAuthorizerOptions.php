<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

class DeprecatedAuthorizerOptions
{
    public function __construct(
        public string $authTransport, // 'ajax' | 'jsonp'
        public string $authEndpoint,
        public ?DeprecatedAuthOptions $auth = null,
    ) {}
}
