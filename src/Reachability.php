<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

interface Reachability
{
    public function isOnline(): bool;
}
