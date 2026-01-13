<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

/**
 * Represents a member in a presence channel.
 */
readonly class Member
{
    public function __construct(
        public string $id,
        public ?array $info = null,
    ) {}
}
