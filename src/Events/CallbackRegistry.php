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
        return $this->callbacks[self::prefix($name)];
    }

    public function add(string $name, Closure $callback, mixed $context): void
    {
        $prefixedEventName = self::prefix($name);
        $this->callbacks[$prefixedEventName] ??= [];
        $this->callbacks[$prefixedEventName][] = new Callback(
            fn: $callback,
            context: $context
        );
    }

    public function remove(?string $name = null, ?Closure $callback = null, mixed $context = null): void
    {
        // if (!name && !callback && !context) {
        //   this._callbacks = {};
        //   return;
        // }

        // var names = name ? [prefix(name)] : Collections.keys(this._callbacks);

        // if (callback || context) {
        //   this.removeCallback(names, callback, context);
        // } else {
        //   this.removeAllCallbacks(names);
        // }
    }

    /**
     * @param  string[]  $names
     */
    private function removeCallback(array $names, Closure $callback, mixed $context): void
    {
        // Collections.apply(
        //   names,
        //   function(name) {
        //     this._callbacks[name] = Collections.filter(
        //       this._callbacks[name] || [],
        //       function(binding) {
        //         return (
        //           (callback && callback !== binding.fn) ||
        //           (context && context !== binding.context)
        //         );
        //       }
        //     );
        //     if (this._callbacks[name].length === 0) {
        //       delete this._callbacks[name];
        //     }
        //   },
        //   this
        // );
    }

    /**
     * @param  string[]  $names
     */
    private function removeAllCallbacks(array $names): void
    {
        //   Collections.apply(
        //     names,
        //     function(name) {
        //       delete this._callbacks[name];
        //     },
        //     this
        //   );
    }

    public static function prefix(string $name): string
    {
        return "_{$name}";
    }
}
