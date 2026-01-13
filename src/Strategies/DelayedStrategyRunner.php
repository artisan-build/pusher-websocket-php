<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Utils\Timers\OneOffTimer;

class DelayedStrategyRunner implements StrategyRunner
{
    public function __construct(
        private OneOffTimer $timer,
        private RunnerHolder $runnerHolder,
        private MinPriorityHolder $minPriorityHolder,
    ) {}

    public function abort(): void
    {
        $this->timer->ensureAborted();
        $this->runnerHolder->runner?->abort();
    }

    public function forceMinPriority(int $p): void
    {
        $this->minPriorityHolder->value = $p;
        $this->runnerHolder->runner?->forceMinPriority($p);
    }
}
