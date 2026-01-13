<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection\Protocol\MessageTypes;

readonly class PusherEvent
{
    // ORIGINAL
    // interface PusherEvent {
    //     event: string;
    //     channel?: string;
    //     data?: any;
    //     user_id?: string;
    // }

    public function __construct(
        public string $event,
        public ?string $channel = null,
        public mixed $data = null,
        public ?string $user_id = null,
    ) {}
}
