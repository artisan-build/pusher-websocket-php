<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

class UserAuthenticationRequestParams
{
    public function __construct(
        public string $socketId,
    ) {}
}
