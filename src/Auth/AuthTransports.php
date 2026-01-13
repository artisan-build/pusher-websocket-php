<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

class AuthTransports implements Arrayable, ArrayAccess
{
    /**
     * @param  array<string, AuthTransport>  $transports
     */
    public function __construct(
        private array $transports = [],
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->transports[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->transports[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->transports[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->transports[$offset]);
    }

    public function toArray(): array
    {
        return $this->transports;
    }
}
