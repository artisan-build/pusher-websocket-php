<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

class ChannelAuthorizationRequestParams
{
    public function __construct(
        public string $socketId,
        public string $channelName,
    ) {}
}
