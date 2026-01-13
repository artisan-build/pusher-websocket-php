<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use ArtisanBuild\Pusher\Errors;
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Utils\Collections;
use ArtisanBuild\Pusher\Utils\Factory;
use ArtisanBuild\Pusher\Utils\UrlStore;

/**
 * Handles a channel map.
 */
class Channels
{
    public ChannelTable $channels;

    public function __construct()
    {
        $this->channels = new ChannelTable;
    }

    /**
     * Creates or retrieves an existing channel by its name.
     */
    public function add(string $name, Pusher $pusher): Channel
    {
        if (! $this->channels[$name]) {
            $this->channels[$name] = self::createChannel($name, $pusher);
        }

        return $this->channels[$name];
    }

    /**
     * Returns a list of all channels
     *
     * @return Channel[]
     */
    public function all(): array
    {
        return Collections::values($this->channels);
    }

    /**
     * Finds a channel by its name.
     */
    public function find(string $name): ?Channel
    {
        return $this->channels[$name];
    }

    /**
     * Removes a channel from the map.
     */
    public function remove(string $name): Channel
    {
        $channel = $this->channels[$name];
        unset($this->channels[$name]);

        return $channel;
    }

    /**
     * Proxies disconnection signal to all channels.
     */
    public function disconnect(): void
    {
        Collections::objectApply($this->channels, function (Channel $channel): void {
            $channel->disconnect();
        });
    }

    public static function createChannel(string $name, Pusher $pusher): Channel
    {
        if (strpos($name, 'private-encrypted-') === 0) {
            if ($pusher->config->nacl) {
                return Factory::createEncryptedChannel($name, $pusher, $pusher->config->nacl);
            }
            $errMsg = 'Tried to subscribe to a private-encrypted- channel but no nacl implementation available';
            $suffix = UrlStore::buildLogSuffix('encryptedChannelSupport');
            throw new Errors\UnsupportedFeature("{$errMsg}. {$suffix}");
        } elseif (strpos($name, 'private-') === 0) {
            return Factory::createPrivateChannel($name, $pusher);
        } elseif (strpos($name, 'presence-') === 0) {
            return Factory::createPresenceChannel($name, $pusher);
        } elseif (strpos($name, '#') === 0) {
            throw new Errors\BadChannelName("Cannot create a channel with name \"{$name}\".");
        } else {
            return Factory::createChannel($name, $pusher);
        }
    }
}
