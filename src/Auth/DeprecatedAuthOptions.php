<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

class DeprecatedAuthOptions
{
    public function __construct(
        public mixed $params = null,
        public mixed $headers = null,
    ) {}
}
