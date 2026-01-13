<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils\Timers;

use Closure;

/**
 * One-off timer abstraction.
 *
 * In PHP, this requires an event loop implementation.
 * The callback will be executed after the specified delay.
 */
class OneOffTimer extends AbstractTimer
{
    public function __construct(int $delay, Closure $callback)
    {
        // In PHP we need an event loop for true async timers
        // For now, this is a placeholder that executes immediately
        // TODO: Integrate with ReactPHP or similar event loop
        parent::__construct(
            fn (Closure $cb, int $delay): int => self::setTimeout($cb, $delay),
            function (int $timer): void { self::clearTimeout($timer); },
            $delay,
            function (mixed $timer) use ($callback): mixed {
                $callback();

                return null;
            }
        );
    }

    private static array $timers = [];

    private static int $nextId = 1;

    private static function setTimeout(Closure $callback, int $delay): int
    {
        $id = self::$nextId++;
        // In a real implementation, this would schedule with an event loop
        // For now, execute immediately (synchronous fallback)
        self::$timers[$id] = $callback;
        $callback();

        return $id;
    }

    private static function clearTimeout(int $timer): void
    {
        unset(self::$timers[$timer]);
    }
}
