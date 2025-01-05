<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Events\Dispatcher as EventsDispatcher;

class WatchlistFacade extends EventsDispatcher
{
    private Pusher $pusher;

    public function __construct(Pusher $pusher)
    {
        parent::__construct(failThrough: function ($eventName, $data) {
            (new Logger)->debug("No callbacks on watchlist events for {$eventName}");
        });

        $this->pusher = $pusher;
        $this->bindWatchlistInternalEvent();
    }

    public function handleEvent($pusherEvent)
    {
        foreach ($pusherEvent['data']['events'] as $watchlistEvent) {
            $this->emit($watchlistEvent['name'], $watchlistEvent);
        }
    }

    private function bindWatchlistInternalEvent()
    {
        $this->pusher->connection->bind('message', function ($pusherEvent) {
            $eventName = $pusherEvent['event'];
            if ($eventName === 'pusher_internal:watchlist_events') {
                $this->handleEvent($pusherEvent);
            }
        });
    }
}
