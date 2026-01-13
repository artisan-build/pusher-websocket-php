<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

use Closure;

interface Ajax
{
    public function open(
        string $method,
        string $url,
        bool $async = true,
        ?string $user = null,
        ?string $password = null
    ): void;

    public function send(mixed $payload = null): void;

    public function setRequestHeader(string $key, string $value): void;

    public function getReadyState(): int;

    public function getResponseText(): string;

    public function getStatus(): int;

    public function setWithCredentials(bool $value): void;

    public function setOnReadyStateChange(?Closure $callback): void;

    public function setOnTimeout(?Closure $callback): void;

    public function setOnError(?Closure $callback): void;

    public function setOnProgress(?Closure $callback): void;

    public function setOnLoad(?Closure $callback): void;

    public function abort(): void;
}
