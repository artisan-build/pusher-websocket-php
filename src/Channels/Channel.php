<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationData;
use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;
use ArtisanBuild\Pusher\Errors;
use ArtisanBuild\Pusher\Events\Dispatcher as EventsDispatcher;
use ArtisanBuild\Pusher\Logger;
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Utils\UrlStore;
use Exception;
use React\EventLoop\Loop;

/**
 * Provides base public channel interface with an event emitter.
 *
 * Emits:
 * - pusher:subscription_succeeded - after subscribing successfully
 * - other non-internal events
 */
class Channel extends EventsDispatcher
{
    public string $name;

    public Pusher $pusher;

    public bool $subscribed;

    public bool $subscriptionPending;

    public bool $subscriptionCancelled;

    public ?int $subscriptionCount = null;

    public function __construct(string $name, Pusher $pusher)
    {
        parent::__construct(failThrough: function (string $event, mixed $data) use ($name): void {
            Logger::debug("No callbacks on {$name} for {$event}");
        });

        $this->name = $name;
        $this->pusher = $pusher;
        $this->subscribed = false;
        $this->subscriptionPending = false;
        $this->subscriptionCancelled = false;
    }

    /**
     * Skips authorization, since public channels don't require it.
     */
    public function authorize(string $socketId, ChannelAuthorizationCallback $callback): void
    {
        $callback(null, new ChannelAuthorizationData(auth: ''));
    }

    /**
     * Triggers an event
     */
    public function trigger(string $event, mixed $data): mixed
    {
        if (strpos($event, 'client-') !== 0) {
            throw new Errors\BadEventName(
                "Event '{$event}' does not start with 'client-'"
            );
        }
        if (! $this->subscribed) {
            $suffix = UrlStore::buildLogSuffix('triggeringClientEvents');
            Logger::warn(
                "Client event triggered before channel 'subscription_succeeded' event. {$suffix}"
            );
        }

        return $this->pusher->send_event($event, $data, $this->name);
    }

    /**
     * Signals disconnection to the channel. For internal use only.
     */
    public function disconnect(): void
    {
        $this->subscribed = false;
        $this->subscriptionPending = false;
    }

    /**
     * Handles a PusherEvent. For internal use only.
     */
    public function handleEvent(PusherEvent $event): void
    {
        $eventName = $event->event;
        $data = $event->data;
        if ($eventName === 'pusher_internal:subscription_succeeded') {
            $this->handleSubscriptionSucceededEvent($event);
        } elseif ($eventName === 'pusher_internal:subscription_count') {
            $this->handleSubscriptionCountEvent($event);
        } elseif (strpos($eventName, 'pusher_internal:') !== 0) {
            $metadata = new Metadata;
            $this->emit($eventName, $data, $metadata);
        }
    }

    public function handleSubscriptionSucceededEvent(PusherEvent $event): void
    {
        $this->subscriptionPending = false;
        $this->subscribed = true;
        if ($this->subscriptionCancelled) {
            $this->pusher->unsubscribe($this->name);
        } else {
            $this->emit('pusher:subscription_succeeded', $event->data);
        }
    }

    public function handleSubscriptionCountEvent(PusherEvent $event): void
    {
        if (is_array($event->data) && isset($event->data['subscription_count'])) {
            $this->subscriptionCount = (int) $event->data['subscription_count'];
        }

        $this->emit('pusher:subscription_count', $event->data);
    }

    /**
     * Sends a subscription request. For internal use only.
     */
    public function subscribe(): void
    {
        if ($this->subscribed) {
            return;
        }
        $this->subscriptionPending = true;
        $this->subscriptionCancelled = false;
        $this->authorize(
            $this->pusher->connection->socket_id,
            new ChannelAuthorizationCallback(function (?Exception $error, ChannelAuthorizationData $data) {
                if ($error) {
                    $this->subscriptionPending = false;
                    Logger::error($error->__toString());

                    $errorData = [
                        'type' => 'AuthError',
                        'error' => $error->getMessage(),
                    ];

                    if ($error instanceof Errors\HTTPAuthError) {
                        $errorData['status'] = $error->status;
                    }

                    // Defer error emission to next tick to allow bindings to be set up
                    // This matches the async behavior of the JavaScript implementation
                    Loop::futureTick(fn () => $this->emit('pusher:subscription_error', $errorData));
                } else {
                    $this->pusher->send_event('pusher:subscribe', [
                        'auth' => $data->auth,
                        'channel_data' => $data->channel_data,
                        'channel' => $this->name,
                    ]);
                }
            })
        );
    }

    /**
     * Sends an unsubscription request. For internal use only.
     */
    public function unsubscribe(): void
    {
        $this->subscribed = false;
        $this->pusher->send_event('pusher:unsubscribe', [
            'channel' => $this->name,
        ]);
    }

    /**
     * Cancels an in progress subscription. For internal use only.
     */
    public function cancelSubscription(): void
    {
        $this->subscriptionCancelled = true;
    }

    /**
     * Reinstates an in progress subscripiton. For internal use only.
     */
    public function reinstateSubscription(): void
    {
        $this->subscriptionCancelled = false;
    }
}
