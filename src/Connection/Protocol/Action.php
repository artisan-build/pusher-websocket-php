<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection\Protocol;

class Action
{
    public function __construct(
        public string $action,
        public ?string $id = null,
        public ?int $activityTimeout = null,
        public mixed $error = null,
    ) {}
}
