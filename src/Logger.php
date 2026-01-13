<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use Closure;
use RuntimeException;

class Logger
{
    public static function debug(mixed ...$args): void
    {
        self::log(self::globalLog(...), $args);
    }

    public static function warn(mixed ...$args): void
    {
        self::log(self::globalLogWarn(...), $args);
    }

    public static function error(mixed ...$args): void
    {
        self::log(self::globalLogError(...), $args);
    }

    private static function globalLog(string $message): void
    {
        // if (global.console && global.console.log) {
        //   global.console.log(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    private static function globalLogWarn(string $message): void
    {
        // if (global.console && global.console.warn) {
        //   global.console.warn(message);
        // } else {
        //   this.globalLog(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    private static function globalLogError(string $message): void
    {
        // if (global.console && global.console.error) {
        //   global.console.error(message);
        // } else {
        //   this.globalLogWarn(message);
        // }
        throw new RuntimeException("Not Implemented Yet.\n\nMessage: {$message}");
    }

    /**
     * @param  array<int, mixed>  $args
     */
    private static function log(
        Closure $defaultLoggingFunction,
        array $args,
    ): void {
        // var message = stringify.apply(this, arguments);
        // if (Pusher.log) {
        //   Pusher.log(message);
        // } else if (Pusher.logToConsole) {
        //   const log = defaultLoggingFunction.bind(this);
        //   log(message);
        // }
        throw new RuntimeException('Not Implemented Yet.');
    }
}
