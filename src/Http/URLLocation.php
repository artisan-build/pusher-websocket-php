<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

class URLLocation
{
    public function __construct(
        public string $base,
        public string $queryString,
    ) {}
}
