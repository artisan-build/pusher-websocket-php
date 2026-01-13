<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils\Timers;

use Closure;

abstract class AbstractTimer
{
    protected ?Closure $clear = null;

    protected ?int $timer = null;

    public function __construct(
        Closure $set,
        Closure $clear,
        int $delay,
        Closure $callback
    ) {
        $this->clear = $clear;
        $this->timer = $set(function () use ($callback) {
            if ($this->timer !== null) {
                $this->timer = $callback($this->timer);
            }
        }, $delay);
    }

    /**
     * Returns whether the timer is still running.
     */
    public function isRunning(): bool
    {
        return $this->timer !== null;
    }

    /**
     * Aborts a timer when it's running.
     */
    public function ensureAborted(): void
    {
        if ($this->timer !== null) {
            ($this->clear)($this->timer);
            $this->timer = null;
        }
    }
}
