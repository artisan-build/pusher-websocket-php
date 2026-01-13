<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use Closure;

interface Strategy
{
    public function isSupported(): bool;

    public function connect(int $minPriority, Closure $callback): StrategyRunner;
}
