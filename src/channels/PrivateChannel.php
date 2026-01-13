<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Channels;

use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationCallback;

/**
 * Extends public channels to provide private channel interface.
 */
class PrivateChannel extends Channel
{
    /**
     * Authorizes the connection to use the channel.
     */
    public function authorize(string $socketId, ChannelAuthorizationCallback $callback): mixed
    {
        return $this->pusher->config->channelAuthorizer(
            [
                'channelName' => $this->name,
                'socketId' => $socketId,
            ],
            $callback,
        );
    }
}
