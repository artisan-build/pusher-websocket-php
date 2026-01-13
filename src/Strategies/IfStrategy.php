<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use Closure;

/**
 * Proxies method calls to one of substrategies basing on the test function.
 */
class IfStrategy implements Strategy
{
    /** @var Closure(): bool */
    public Closure $test;

    public Strategy $trueBranch;

    public Strategy $falseBranch;

    /**
     * @param  Closure(): bool  $test
     */
    public function __construct(
        Closure $test,
        Strategy $trueBranch,
        Strategy $falseBranch
    ) {
        $this->test = $test;
        $this->trueBranch = $trueBranch;
        $this->falseBranch = $falseBranch;
    }

    public function isSupported(): bool
    {
        $branch = ($this->test)() ? $this->trueBranch : $this->falseBranch;

        return $branch->isSupported();
    }

    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        $branch = ($this->test)() ? $this->trueBranch : $this->falseBranch;

        return $branch->connect($minPriority, $callback);
    }
}
