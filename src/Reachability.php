<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Events\Dispatcher;

interface Reachability
{
    public function isOnline(): bool;
}
