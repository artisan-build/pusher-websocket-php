<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

/**
 * Mutable holder for minPriority value, enabling shared state between
 * timer callbacks and strategy runners without primitive reference passing.
 */
class MinPriorityHolder
{
    public function __construct(
        public int $value,
    ) {}
}
