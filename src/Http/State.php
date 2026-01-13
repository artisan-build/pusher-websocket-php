<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

enum State: int
{
    case CONNECTING = 0;
    case OPEN = 1;
    case CLOSED = 3;
}
