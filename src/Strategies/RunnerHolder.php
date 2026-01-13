<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

/**
 * Mutable holder for a StrategyRunner, enabling shared state between
 * timer callbacks and strategy runners without reference passing.
 */
class RunnerHolder
{
    public function __construct(
        public ?StrategyRunner $runner = null,
    ) {}
}
