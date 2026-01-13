<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

class Logger
{
    public static function debug(mixed ...$args): void
    {
        if (Pusher::$logToConsole) {
            self::output('DEBUG', $args);
        }
    }

    public static function warn(mixed ...$args): void
    {
        if (Pusher::$logToConsole) {
            self::output('WARN', $args);
        }
    }

    public static function error(mixed ...$args): void
    {
        if (Pusher::$logToConsole) {
            self::output('ERROR', $args);
        }
    }

    /**
     * @param  array<int, mixed>  $args
     */
    private static function output(string $level, array $args): void
    {
        $message = self::stringify($args);
        fwrite(STDERR, "[Pusher {$level}] {$message}\n");
    }

    /**
     * @param  array<int, mixed>  $args
     */
    private static function stringify(array $args): string
    {
        $parts = [];
        foreach ($args as $arg) {
            if (is_string($arg)) {
                $parts[] = $arg;
            } elseif (is_array($arg) || is_object($arg)) {
                $parts[] = json_encode($arg);
            } else {
                $parts[] = (string) $arg;
            }
        }

        return implode(' ', $parts);
    }
}
