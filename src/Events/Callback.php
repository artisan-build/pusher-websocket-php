<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Events;

use Closure;

class Callback
{
    public function __construct(
        public Closure $fn,
        public mixed $context,
    ) {}
}
