<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

class TimelineSenderOptions
{
    // export interface TimelineSenderOptions {
    // host?: string;
    // port?: number;
    // path?: string;
    // }

    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $path = null,
    ) {}
}
