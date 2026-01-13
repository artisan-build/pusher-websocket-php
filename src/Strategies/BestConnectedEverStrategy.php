<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use Closure;

/**
 * Launches all substrategies and emits prioritized connected transports.
 */
class BestConnectedEverStrategy implements Strategy
{
    /** @var Strategy[] */
    public array $strategies;

    /**
     * @param  Strategy[]  $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
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
        return $this->connectStrategies($this->strategies, $minPriority, function (int $i, array &$runners) use ($callback): Closure {
            return function (mixed $error, mixed $handshake) use ($i, &$runners, $callback): void {
                $runners[$i]['error'] = $error;
                if ($error) {
                    if ($this->allRunnersFailed($runners)) {
                        $callback(true);
                    }

                    return;
                }
                foreach ($runners as $runner) {
                    $runner['runner']->forceMinPriority($handshake->transport->priority);
                }
                $callback(null, $handshake);
            };
        });
    }

    /**
     * Connects to all strategies in parallel.
     *
     * @param  Strategy[]  $strategies
     */
    private function connectStrategies(array $strategies, int $minPriority, Closure $callbackBuilder): StrategyRunner
    {
        $runners = [];
        foreach ($strategies as $i => $strategy) {
            $runners[$i] = [
                'runner' => $strategy->connect($minPriority, $callbackBuilder($i, $runners)),
                'error' => null,
                'aborted' => false,
            ];
        }

        return new class($runners) implements StrategyRunner
        {
            public function __construct(private array &$runners) {}

            public function abort(): void
            {
                foreach ($this->runners as &$runner) {
                    if (! $runner['error'] && ! $runner['aborted']) {
                        $runner['runner']->abort();
                        $runner['aborted'] = true;
                    }
                }
            }

            public function forceMinPriority(int $p): void
            {
                foreach ($this->runners as $runner) {
                    $runner['runner']->forceMinPriority($p);
                }
            }
        };
    }

    private function allRunnersFailed(array $runners): bool
    {
        foreach ($runners as $runner) {
            if (! $runner['error']) {
                return false;
            }
        }

        return true;
    }
}
