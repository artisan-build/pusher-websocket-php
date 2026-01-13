<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use InvalidArgumentException;

/**
 * Event payload for member_added/member_removed events.
 */
readonly class MemberData
{
    public function __construct(
        public string $user_id,
        public ?array $user_info = null,
    ) {}

    public static function fromArray(array $data): self
    {
        if (! isset($data['user_id'])) {
            throw new InvalidArgumentException('Missing "user_id" in member data');
        }

        return new self(
            user_id: $data['user_id'],
            user_info: $data['user_info'] ?? null,
        );
    }
}
