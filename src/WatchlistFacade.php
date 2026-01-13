<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;
use ArtisanBuild\Pusher\Events\Dispatcher as EventsDispatcher;

class WatchlistFacade extends EventsDispatcher
{
    private Pusher $pusher;

    public function __construct(Pusher $pusher)
    {
        parent::__construct(failThrough: function (string $eventName, mixed $data): void {
            Logger::debug("No callbacks on watchlist events for {$eventName}");
        });

        $this->pusher = $pusher;
        $this->bindWatchlistInternalEvent();
    }

    public function handleEvent(PusherEvent $pusherEvent): void
    {
        $events = is_array($pusherEvent->data) ? ($pusherEvent->data['events'] ?? []) : [];
        foreach ($events as $watchlistEvent) {
            $this->emit($watchlistEvent['name'], $watchlistEvent);
        }
    }

    private function bindWatchlistInternalEvent(): void
    {
        $this->pusher->connection->bind('message', function (mixed $pusherEvent): void {
            $eventName = $pusherEvent instanceof PusherEvent ? $pusherEvent->event : ($pusherEvent['event'] ?? '');
            if ($eventName === 'pusher_internal:watchlist_events') {
                if ($pusherEvent instanceof PusherEvent) {
                    $this->handleEvent($pusherEvent);
                }
            }
        });
    }
}
