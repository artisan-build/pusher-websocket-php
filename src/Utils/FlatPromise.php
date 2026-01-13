<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils;

use Closure;

/**
 * A deferred promise pattern - returns a promise along with its resolve/reject functions.
 */
class FlatPromise
{
    public mixed $result = null;

    public mixed $error = null;

    public bool $resolved = false;

    public bool $rejected = false;

    public ?Closure $onResolve = null;

    public ?Closure $onReject = null;

    public function resolve(mixed $value): void
    {
        if ($this->resolved || $this->rejected) {
            return;
        }
        $this->resolved = true;
        $this->result = $value;
        if ($this->onResolve) {
            ($this->onResolve)($value);
        }
    }

    public function reject(mixed $error): void
    {
        if ($this->resolved || $this->rejected) {
            return;
        }
        $this->rejected = true;
        $this->error = $error;
        if ($this->onReject) {
            ($this->onReject)($error);
        }
    }

    public function then(Closure $onResolve, ?Closure $onReject = null): self
    {
        $this->onResolve = $onResolve;
        if ($onReject) {
            $this->onReject = $onReject;
        }
        if ($this->resolved) {
            $onResolve($this->result);
        } elseif ($this->rejected && $onReject) {
            $onReject($this->error);
        }

        return $this;
    }

    /**
     * @return array{promise: FlatPromise, resolve: Closure, reject: Closure}
     */
    public static function create(): array
    {
        $promise = new self;

        return [
            'promise' => $promise,
            'resolve' => function (mixed $value) use ($promise): void {
                $promise->resolve($value);
            },
            'reject' => function (mixed $error) use ($promise): void {
                $promise->reject($error);
            },
        ];
    }
}
