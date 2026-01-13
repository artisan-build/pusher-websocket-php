<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use Closure;

interface DeprecatedChannelAuthorizer
{
    public function authorize(string $socketId, Closure $callback): void;
}
