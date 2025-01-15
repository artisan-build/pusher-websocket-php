<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils;

use ArtisanBuild\Pusher\Channels\Channel;
// import AssistantToTheTransportManager from '../transports/assistant_to_the_transport_manager';
// import PingDelayOptions from '../transports/ping_delay_options';
// import Transport from '../transports/transport';
// import TransportManager from '../transports/transport_manager';
// import Handshake from '../connection/handshake';
// import TransportConnection from '../transports/transport_connection';
// import SocketHooks from '../http/socket_hooks';
// import HTTPSocket from '../http/http_socket';
use ArtisanBuild\Pusher\Channels\Channels;
use ArtisanBuild\Pusher\Channels\EncryptedChannel;
use ArtisanBuild\Pusher\Channels\PresenceChannel;
use ArtisanBuild\Pusher\Channels\PrivateChannel;
// import ConnectionManager from '../connection/connection_manager';
// import ConnectionManagerOptions from '../connection/connection_manager_options';
// import Ajax from '../http/ajax';
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Timeline\Timeline;
use ArtisanBuild\Pusher\Timeline\TimelineSender;
use ArtisanBuild\Pusher\Timeline\TimelineSenderOptions;
use Closure;

// import { Config } from '../config';
// import * as nacl from 'tweetnacl';

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

    /**
     * @param  fn(HandshakePayload):void  $callback
     */
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
