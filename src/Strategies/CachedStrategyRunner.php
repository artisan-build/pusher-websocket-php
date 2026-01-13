<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

class CachedStrategyRunner implements StrategyRunner
{
    public function __construct(
        private RunnerHolder $runnerHolder,
        private MinPriorityHolder $minPriorityHolder,
    ) {}

    public function abort(): void
    {
        $this->runnerHolder->runner?->abort();
    }

    public function forceMinPriority(int $p): void
    {
        $this->minPriorityHolder->value = $p;
        $this->runnerHolder->runner?->forceMinPriority($p);
    }
}
