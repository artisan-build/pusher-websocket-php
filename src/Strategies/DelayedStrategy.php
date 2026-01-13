<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Utils\Timers\OneOffTimer;
use Closure;

/**
 * Runs substrategy after specified delay.
 *
 * Options:
 * - delay - time in milliseconds to delay the substrategy attempt
 */
class DelayedStrategy implements Strategy
{
    public function __construct(
        public Strategy $strategy,
        public int $delay,
    ) {}

    public function isSupported(): bool
    {
        return $this->strategy->isSupported();
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $strategy = $this->strategy;
        $runnerHolder = new RunnerHolder;
        $minPriorityHolder = new MinPriorityHolder($minPriority);

        $timer = new OneOffTimer($this->delay, function () use ($strategy, $minPriorityHolder, $callback, $runnerHolder): void {
            $runnerHolder->runner = $strategy->connect($minPriorityHolder->value, $callback);
        });

        return new DelayedStrategyRunner($timer, $runnerHolder, $minPriorityHolder);
    }
}
