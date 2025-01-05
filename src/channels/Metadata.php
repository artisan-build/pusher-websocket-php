<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

class Metadata
{
    public function __construct(
        public ?string $user_id = null,
    ) {}
}
