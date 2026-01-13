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
    public Strategy $strategy;

    public int $delay;

    public function __construct(Strategy $strategy, int $delay)
    {
        $this->strategy = $strategy;
        $this->delay = $delay;
    }

    public function isSupported(): bool
    {
        return $this->strategy->isSupported();
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $strategy = $this->strategy;
        $runner = null;
        $timer = new OneOffTimer($this->delay, function () use ($strategy, &$minPriority, $callback, &$runner) {
            $runner = $strategy->connect($minPriority, $callback);
        });

        return new class($timer, $runner, $minPriority) implements StrategyRunner {
            public function __construct(
                private OneOffTimer $timer,
                private ?StrategyRunner &$runner,
                private int &$minPriority,
            ) {}

            public function abort(): void
            {
                $this->timer->ensureAborted();
                if ($this->runner) {
                    $this->runner->abort();
                }
            }

            public function forceMinPriority(int $p): void
            {
                $this->minPriority = $p;
                if ($this->runner) {
                    $this->runner->forceMinPriority($p);
                }
            }
        };
    }
}
