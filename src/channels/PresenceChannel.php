<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationData;
use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;
use ArtisanBuild\Pusher\Logger;
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Utils\UrlStore;
use Exception;

class PresenceChannel extends PrivateChannel
{
    public Members $members;

    /**
     * Adds presence channel functionality to private channels.
     */
    public function __construct(string $name, Pusher $pusher)
    {
        parent::__construct($name, $pusher);
        $this->members = new Members;
    }

    /**
     * Authorizes the connection as a member of the channel.
     */
    public function authorize(string $socketId, ChannelAuthorizationCallback $callback)
    {
        // TODO: Implement this method
        // This needs some async/await react goodness.
        throw new Exception('Not implemented');
        // parent::authorize($socketId, async (error, authData) => {
        //   if (!error) {
        //     authData = authData as ChannelAuthorizationData;
        //     if (authData.channel_data != null) {
        //       var channelData = JSON.parse(authData.channel_data);
        //       this.members.setMyID(channelData.user_id);
        //     } else {
        //       await this.pusher.user.signinDonePromise;
        //       if (this.pusher.user.user_data != null) {
        //         // If the user is signed in, get the id of the authenticated user
        //         // and allow the presence authorization to continue.
        //         this.members.setMyID(this.pusher.user.user_data.id);
        //       } else {
        //         let suffix = UrlStore.buildLogSuffix('authorizationEndpoint');
        //         Logger.error(
        //           `Invalid auth response for channel '${this.name}', ` +
        //             `expected 'channel_data' field. ${suffix}, ` +
        //             `or the user should be signed in.`
        //         );
        //         callback('Invalid auth response');
        //         return;
        //       }
        //     }
        //   }
        //   callback(error, authData);
        // });
    }

    /**
     * Handles presence and subscription events. For internal use only.
     */
    public function handleEvent(PusherEvent $event)
    {
        $eventName = $event->event;
        if (strpos($eventName, 'pusher_internal:') === 0) {
            $this->handleInternalEvent($event);
        } else {
            $data = $event->data;
            $metadata = new Metadata;
            if ($event->user_id) {
                $metadata->user_id = $event->user_id;
            }
            $this->emit($eventName, $data, $metadata);
        }
    }

    public function handleInternalEvent(PusherEvent $event)
    {
        $eventName = $event->event;
        $data = $event->data;
        switch ($eventName) {
            case 'pusher_internal:subscription_succeeded':
                $this->handleSubscriptionSucceededEvent($event);
                break;
            case 'pusher_internal:subscription_count':
                $this->handleSubscriptionCountEvent($event);
                break;
            case 'pusher_internal:member_added':
                $addedMember = $this->members->addMember($data);
                $this->emit('pusher:member_added', $addedMember);
                break;
            case 'pusher_internal:member_removed':
                $removedMember = $this->members->removeMember($data);
                if ($removedMember) {
                    $this->emit('pusher:member_removed', $removedMember);
                }
                break;
        }
    }

    public function handleSubscriptionSucceededEvent(PusherEvent $event)
    {
        $this->subscriptionPending = false;
        $this->subscribed = true;
        if ($this->subscriptionCancelled) {
            $this->pusher->unsubscribe($this->name);
        } else {
            $this->members->onSubscription($event->data);
            $this->emit('pusher:subscription_succeeded', $this->members);
        }
    }

    /**
     * Resets the channel state, including members map. For internal use only.
     */
    public function disconnect()
    {
        $this->members->reset();
        parent::disconnect();
    }
}
