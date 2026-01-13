<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Events;

use ArtisanBuild\Pusher\Utils\Collections;
use Closure;

class CallbackRegistry
{
    private CallbackTable $callbacks;

    public function __construct()
    {
        $this->callbacks = new CallbackTable;
    }

    /**
     * @return array<Callback>
     */
    public function get(string $name): array
    {
        return $this->callbacks[self::prefix($name)] ?? [];
    }

    public function add(string $name, Closure $callback, mixed $context): void
    {
        $prefixedEventName = self::prefix($name);
        $existing = $this->callbacks[$prefixedEventName] ?? [];
        $existing[] = new Callback(
            fn: $callback,
            context: $context
        );
        $this->callbacks[$prefixedEventName] = $existing;
    }

    public function remove(?string $name = null, ?Closure $callback = null, mixed $context = null): void
    {
        if ($name === null && $callback === null && $context === null) {
            $this->callbacks = new CallbackTable;

            return;
        }

        $names = $name !== null ? [self::prefix($name)] : Collections::keys($this->callbacks);

        if ($callback !== null || $context !== null) {
            $this->removeCallback($names, $callback, $context);
        } else {
            $this->removeAllCallbacks($names);
        }
    }

    /**
     * @param  string[]  $names
     */
    private function removeCallback(array $names, ?Closure $callback, mixed $context): void
    {
        foreach ($names as $eventName) {
            $this->callbacks[$eventName] = Collections::filter(
                $this->callbacks[$eventName] ?? [],
                function (Callback $binding) use ($callback, $context): bool {
                    return ($callback !== null && $callback !== $binding->fn) ||
                           ($context !== null && $context !== $binding->context);
                }
            );
            if (count($this->callbacks[$eventName]) === 0) {
                unset($this->callbacks[$eventName]);
            }
        }
    }

    /**
     * @param  string[]  $names
     */
    private function removeAllCallbacks(array $names): void
    {
        foreach ($names as $eventName) {
            unset($this->callbacks[$eventName]);
        }
    }

    public static function prefix(string $name): string
    {
        return "_{$name}";
    }
}
