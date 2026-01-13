<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Utils\Timers\OneOffTimer;
use Closure;

class Util
{
    public static function now(): int
    {
        return (int) (microtime(true) * 1000);
    }

    public static function defer(Closure $callback): OneOffTimer
    {
        return new OneOffTimer(0, $callback);
    }

    /**
     * Builds a function that will proxy a method call to its first argument.
     *
     * Allows partial application of arguments.
     */
    public static function method(string $name, mixed ...$boundArgs): Closure
    {
        return function ($object) use ($name, $boundArgs) {
            return $object->$name(...$boundArgs);
        };
    }
}
