<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

class TimelineOptions
{
    // export interface TimelineOptions {
    //     level?: Level;
    //     limit?: number;
    //     version?: string;
    //     cluster?: string;
    //     features?: string[];
    //     params?: any;
    //   }

    public function __construct(
        public ?TimelineLevel $level = null,
        public ?int $limit = null,
        public ?string $version = null,
        public ?string $cluster = null,
        public ?array $features = null,
        public mixed $params = null,
    ) {}
}
