<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Events;

use ArrayAccess;

class CallbackTable implements ArrayAccess
{
    // ORIGINAL
    // interface CallbackTable {
    //   [index: string]: Callback[];
    // }

    // TODO: implement Arrayable
    // https://www.php.net/manual/en/class.arrayaccess.php
    // https://stackoverflow.com/questions/11841637/php-arrayable-interface

    /**
     * @var array<string, Callback[]>
     */
    public array $container = [];

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}
