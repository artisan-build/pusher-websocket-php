<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

enum TimelineLevel: int
{
    case ERROR = 3;
    case INFO = 6;
    case DEBUG = 7;
}
