<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Events;

use ArtisanBuild\Pusher\Channels\Metadata;
use ArtisanBuild\Pusher\Utils\Collections;
use Closure;

/**
 * Manages callback bindings and event emitting.
 */
class Dispatcher
{
    public CallbackRegistry $callbacks;

    public array $global_callbacks;

    public ?Closure $failThrough;

    public function __construct(?Closure $failThrough = null)
    {
        $this->callbacks = new CallbackRegistry;
        $this->global_callbacks = [];
        $this->failThrough = $failThrough;
    }

    public function bind(string $eventName, Closure $callback, mixed $context = null): static
    {
        $this->callbacks->add($eventName, $callback, $context);

        return $this;
    }

    public function bind_global(Closure $callback): static
    {
        $this->global_callbacks[] = $callback;

        return $this;
    }

    public function unbind(?string $eventName = null, ?Closure $callback = null, mixed $context = null): static
    {
        $this->callbacks->remove($eventName, $callback, $context);

        return $this;
    }

    public function unbind_global(?Closure $callback = null): static
    {
        if (! $callback) {
            $this->global_callbacks = [];

            return $this;
        }

        $this->global_callbacks = Collections::filter(
            $this->global_callbacks,
            fn (mixed $c): bool => $c !== $callback,
        );

        return $this;
    }

    public function unbind_all(): static
    {
        $this->unbind();
        $this->unbind_global();

        return $this;
    }

    public function emit(string $eventName, mixed $data = null, ?Metadata $metadata = null): static
    {
        for ($i = 0; $i < count($this->global_callbacks); $i++) {
            $this->global_callbacks[$i]($eventName, $data);
        }

        $callbacks = $this->callbacks->get($eventName);
        $args = [];

        if ($metadata) {
            // if there's a metadata argument, we need to call the callback with both
            // data and metadata regardless of whether data is undefined
            $args[] = $data;
            $args[] = $metadata;
        } elseif ($data) {
            // metadata is undefined, so we only need to call the callback with data
            // if data exists
            $args[] = $data;
        }

        if ($callbacks !== []) {
            foreach ($callbacks as $callback) {
                ($callback->fn)(...$args);
            }
        } elseif ($this->failThrough) {
            ($this->failThrough)($eventName, $data);
        }

        return $this;
    }
}
