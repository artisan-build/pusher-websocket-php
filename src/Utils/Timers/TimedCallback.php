<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils\Timers;

use Closure;

/**
 * Interface for timed callback functions.
 *
 * @phpstan-type TimedCallbackFn Closure(int|null): (int|null)
 */
interface TimedCallback
{
    public function __invoke(?int $timer = null): ?int;
}
