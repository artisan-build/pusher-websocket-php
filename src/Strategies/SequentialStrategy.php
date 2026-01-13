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
        $runner = null;

        $tryNextStrategy = function ($error, $handshake) use (
            &$tryNextStrategy,
            $callback,
            $strategies,
            &$current,
            &$timeout,
            &$minPriority,
            &$runner
        ) {
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
                    $runner = $this->tryStrategy(
                        $strategies[$current],
                        $minPriority,
                        new StrategyOptions(timeout: $timeout, failFast: $this->failFast),
                        $tryNextStrategy
                    );
                } else {
                    $callback(true);
                }
            }
        };

        $runner = $this->tryStrategy(
            $strategies[$current],
            $minPriority,
            new StrategyOptions(timeout: $timeout, failFast: $this->failFast),
            $tryNextStrategy
        );

        return new class($runner, $minPriority) implements StrategyRunner {
            public function __construct(
                private ?StrategyRunner &$runner,
                private int &$minPriority,
            ) {}

            public function abort(): void
            {
                $this->runner->abort();
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

    private function tryStrategy(
        Strategy $strategy,
        int $minPriority,
        StrategyOptions $options,
        Closure $callback
    ): StrategyRunner {
        $timer = null;
        $runner = null;

        if ($options->timeout > 0) {
            $timer = new OneOffTimer($options->timeout, function () use (&$runner, $callback) {
                $runner->abort();
                $callback(true);
            });
        }

        $runner = $strategy->connect($minPriority, function ($error, $handshake) use (&$timer, $options, $callback) {
            if ($error && $timer && $timer->isRunning() && ! $options->failFast) {
                // advance to the next strategy after the timeout
                return;
            }
            if ($timer) {
                $timer->ensureAborted();
            }
            $callback($error, $handshake);
        });

        return new class($timer, $runner) implements StrategyRunner {
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
