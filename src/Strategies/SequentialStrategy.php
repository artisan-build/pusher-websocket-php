<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Utils\Timers\OneOffTimer;
use Closure;

/**
 * Loops through strategies with optional timeouts.
 *
 * Options:
 * - loop - whether it should loop through the substrategy list
 * - timeout - initial timeout for a single substrategy
 * - timeoutLimit - maximum timeout
 */
class SequentialStrategy implements Strategy
{
    /** @var Strategy[] */
    public array $strategies;

    public bool $loop;

    public bool $failFast;

    public ?int $timeout;

    public ?int $timeoutLimit;

    /**
     * @param  Strategy[]  $strategies
     */
    public function __construct(array $strategies, StrategyOptions $options)
    {
        $this->strategies = $strategies;
        $this->loop = (bool) $options->loop;
        $this->failFast = (bool) $options->failFast;
        $this->timeout = $options->timeout;
        $this->timeoutLimit = $options->timeoutLimit;
    }

    public function isSupported(): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isSupported()) {
                return true;
            }
        }

        return false;
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $strategies = $this->strategies;
        $current = 0;
        $timeout = $this->timeout;
        $runnerHolder = new RunnerHolder;
        $minPriorityHolder = new MinPriorityHolder($minPriority);

        $tryNextStrategy = function (mixed $error, mixed $handshake) use (
            &$tryNextStrategy,
            $callback,
            $strategies,
            &$current,
            &$timeout,
            $minPriorityHolder,
            $runnerHolder
        ): void {
            if ($handshake) {
                $callback(null, $handshake);
            } else {
                $current = $current + 1;
                if ($this->loop) {
                    $current = $current % count($strategies);
                }

                if ($current < count($strategies)) {
                    if ($timeout) {
                        $timeout = $timeout * 2;
                        if ($this->timeoutLimit) {
                            $timeout = min($timeout, $this->timeoutLimit);
                        }
                    }
                    $runnerHolder->runner = $this->tryStrategy(
                        $strategies[$current],
                        $minPriorityHolder->value,
                        new StrategyOptions(timeout: $timeout, failFast: $this->failFast),
                        $tryNextStrategy
                    );
                } else {
                    $callback(true);
                }
            }
        };

        $runnerHolder->runner = $this->tryStrategy(
            $strategies[$current],
            $minPriorityHolder->value,
            new StrategyOptions(timeout: $timeout, failFast: $this->failFast),
            $tryNextStrategy
        );

        return new SequentialStrategyRunner($runnerHolder, $minPriorityHolder);
    }

    private function tryStrategy(
        Strategy $strategy,
        int $minPriority,
        StrategyOptions $options,
        Closure $callback
    ): StrategyRunner {
        $timer = null;
        /** @var ?StrategyRunner $runner */
        $runner = null;

        if ($options->timeout > 0) {
            $timer = new OneOffTimer($options->timeout, function () use (&$runner, $callback): void {
                if ($runner !== null) {
                    $runner->abort();
                }
                $callback(true);
            });
        }

        $runner = $strategy->connect($minPriority, function (mixed $error, mixed $handshake) use (&$timer, $options, $callback): void {
            if ($error && $timer && $timer->isRunning() && ! $options->failFast) {
                // advance to the next strategy after the timeout
                return;
            }
            if ($timer) {
                $timer->ensureAborted();
            }
            $callback($error, $handshake);
        });

        return new class($timer, $runner) implements StrategyRunner
        {
            public function __construct(
                private ?OneOffTimer $timer,
                private StrategyRunner $runner,
            ) {}

            public function abort(): void
            {
                if ($this->timer) {
                    $this->timer->ensureAborted();
                }
                $this->runner->abort();
            }

            public function forceMinPriority(int $p): void
            {
                $this->runner->forceMinPriority($p);
            }
        };
    }
}
