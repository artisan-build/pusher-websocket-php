<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

class UserAuthenticationData
{
    public function __construct(
        public string $auth,
        public string $user_data,
    ) {}
}
