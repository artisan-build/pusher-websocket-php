<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

use ArtisanBuild\Pusher\Runtimes\Runtime;
use Closure;

class TimelineSender
{
    public string $host;

    public function __construct(
        public Timeline $timeline,
        public TimelineSenderOptions $options = new TimelineSenderOptions,
    ) {}

    public function send(bool $useTLS, ?Closure $callback = null): void
    {
        if ($this->timeline->isEmpty()) {
            return;
        }

        $this->timeline->send(
            Runtime::$TimelineTransport->getAgent($this, $useTLS),
            $callback
        );
    }
}
