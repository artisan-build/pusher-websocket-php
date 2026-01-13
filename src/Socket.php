<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use Closure;

interface Socket
{
    public function send(mixed $payload): void;

    public function ping(): void;

    public function close(?int $code = null, ?string $reason = null): void;

    public function sendRaw(mixed $payload): bool;

    public function setOnOpen(?Closure $callback): void;

    public function setOnError(?Closure $callback): void;

    public function setOnClose(?Closure $callback): void;

    public function setOnMessage(?Closure $callback): void;

    public function setOnActivity(?Closure $callback): void;
}
