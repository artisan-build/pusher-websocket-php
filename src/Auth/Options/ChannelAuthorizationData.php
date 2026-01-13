<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

class ChannelAuthorizationData
{
    public function __construct(
        public string $auth,
        public ?string $channel_data = null,
        public ?string $shared_secret = null,
    ) {}
}
