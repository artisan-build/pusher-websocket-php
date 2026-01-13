<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use Closure;

/**
 * Mutable holder for an unbind callback, resolving the circular dependency
 * where handlers need to call unbindListeners, but unbindListeners needs
 * references to the handlers.
 */
class UnbindHolder
{
    public ?Closure $unbind = null;

    public function call(): void
    {
        if ($this->unbind !== null) {
            ($this->unbind)();
        }
    }
}
