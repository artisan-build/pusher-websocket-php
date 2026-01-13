<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use InvalidArgumentException;

/**
 * Event payload for presence channel subscription_succeeded.
 */
readonly class PresenceData
{
    /**
     * @param  array<string, array|null>  $hash  Map of user IDs to user info
     */
    public function __construct(
        public int $count,
        public array $hash,
    ) {}

    public static function fromArray(array $data): self
    {
        if (! isset($data['presence'])) {
            throw new InvalidArgumentException('Missing "presence" key in subscription data');
        }

        $presence = $data['presence'];

        if (! isset($presence['count'])) {
            throw new InvalidArgumentException('Missing "presence.count" in subscription data');
        }

        if (! isset($presence['hash'])) {
            throw new InvalidArgumentException('Missing "presence.hash" in subscription data');
        }

        return new self(
            count: $presence['count'],
            hash: $presence['hash'],
        );
    }
}
