<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use Closure;

/**
 * Launches the substrategy and terminates on the first open connection.
 */
class FirstConnectedStrategy implements Strategy
{
    public Strategy $strategy;

    public function __construct(Strategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function isSupported(): bool
    {
        return $this->strategy->isSupported();
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $runner = $this->strategy->connect($minPriority, function (mixed $error, mixed $handshake) use (&$runner, $callback): void {
            if ($handshake) {
                $runner->abort();
            }
            $callback($error, $handshake);
        });

        return $runner;
    }
}
