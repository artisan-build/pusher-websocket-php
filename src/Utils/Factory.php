<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils;

use ArtisanBuild\Pusher\Channels\Channel;
use ArtisanBuild\Pusher\Channels\Channels;
use ArtisanBuild\Pusher\Channels\EncryptedChannel;
use ArtisanBuild\Pusher\Channels\PresenceChannel;
use ArtisanBuild\Pusher\Channels\PrivateChannel;
use ArtisanBuild\Pusher\Connection\ConnectionManager;
use ArtisanBuild\Pusher\Connection\ConnectionManagerOptions;
use ArtisanBuild\Pusher\Connection\Handshake\Handshake;
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Timeline\Timeline;
use ArtisanBuild\Pusher\Timeline\TimelineSender;
use ArtisanBuild\Pusher\Timeline\TimelineSenderOptions;
use ArtisanBuild\Pusher\Transports\AssistantToTheTransportManager;
use ArtisanBuild\Pusher\Transports\PingDelayOptions;
use ArtisanBuild\Pusher\Transports\Transport;
use ArtisanBuild\Pusher\Transports\TransportConnection;
use ArtisanBuild\Pusher\Transports\TransportManager;
use Closure;

class Factory
{
    public static function createChannels(): Channels
    {
        return new Channels;
    }

    public static function createConnectionManager(
        string $key,
        ConnectionManagerOptions $options,
    ): ConnectionManager {
        return new ConnectionManager($key, $options);
    }

    public static function createChannel(string $name, Pusher $pusher): Channel
    {
        return new Channel($name, $pusher);
    }

    public static function createPrivateChannel(string $name, Pusher $pusher): PrivateChannel
    {
        return new PrivateChannel($name, $pusher);
    }

    public static function createPresenceChannel(string $name, Pusher $pusher): PresenceChannel
    {
        return new PresenceChannel($name, $pusher);
    }

    public static function createEncryptedChannel(
        string $name,
        Pusher $pusher,
        // nacl $nacl,
    ): EncryptedChannel {
        return new EncryptedChannel(
            $name,
            $pusher,
            // $nacl,
        );
    }

    public static function createTimelineSender(
        Timeline $timeline,
        TimelineSenderOptions $options,
    ): TimelineSender {
        return new TimelineSender($timeline, $options);
    }

    public static function createHandshake(
        TransportConnection $transport,
        Closure $callback,
    ): Handshake {
        return new Handshake($transport, $callback);
    }

    public static function createAssistantToTheTransportManager(
        TransportManager $manager,
        Transport $transport,
        PingDelayOptions $options,
    ): AssistantToTheTransportManager {
        return new AssistantToTheTransportManager($manager, $transport, $options);
    }
}
