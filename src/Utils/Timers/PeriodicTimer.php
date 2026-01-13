<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils\Timers;

use Closure;

/**
 * Periodic timer abstraction.
 *
 * In PHP, this requires an event loop implementation.
 * The callback will be executed repeatedly at the specified interval.
 */
class PeriodicTimer extends AbstractTimer
{
    public function __construct(int $delay, Closure $callback)
    {
        // In PHP we need an event loop for true async timers
        // TODO: Integrate with ReactPHP or similar event loop
        parent::__construct(
            fn (Closure $cb, int $delay) => self::setInterval($cb, $delay),
            fn (int $timer) => self::clearInterval($timer),
            $delay,
            function ($timer) use ($callback) {
                $callback();

                return $timer;
            }
        );
    }

    private static array $timers = [];

    private static int $nextId = 1;

    private static function setInterval(Closure $callback, int $delay): int
    {
        $id = self::$nextId++;
        // In a real implementation, this would schedule with an event loop
        self::$timers[$id] = $callback;

        return $id;
    }

    private static function clearInterval(int $timer): void
    {
        unset(self::$timers[$timer]);
    }
}
