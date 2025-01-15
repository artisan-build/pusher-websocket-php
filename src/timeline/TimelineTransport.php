<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Timeline;

use Closure;

abstract class TimelineTransport
{
    // name: string;
    // getAgent: (
    //     sender: TimelineSender,
    //     useTLS: boolean
    // ) => (data: any, callback: Function) => void;

    public function __construct(
        protected string $name,
    ) {}

    /**
     * @return Closure(mixed $data, Closure $callback): void
     */
    abstract public function getAgent(
        TimelineSender $sender,
        bool $useTLS
    ): Closure;
}
