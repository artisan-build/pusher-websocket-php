<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use ArtisanBuild\Pusher\Utils\Factory;

/**
 * Keeps track of the number of lives left for a transport.
 *
 * In the beginning of a session, transports may be assigned a number of
 * lives. When an AssistantToTheTransportManager instance reports a transport
 * connection closed uncleanly, the transport loses a life. When the number
 * of lives drops to zero, the transport gets disabled by its manager.
 */
class TransportManager
{
    public TransportManagerOptions $options;

    public float $livesLeft;

    public function __construct(?TransportManagerOptions $options = null)
    {
        $this->options = $options ?? new TransportManagerOptions;
        $this->livesLeft = $this->options->lives ?? INF;
    }

    /**
     * Creates an assistant for the transport.
     */
    public function getAssistant(Transport $transport): AssistantToTheTransportManager
    {
        return Factory::createAssistantToTheTransportManager($this, $transport, new PingDelayOptions(
            minPingDelay: $this->options->minPingDelay,
            maxPingDelay: $this->options->maxPingDelay,
        ));
    }

    /**
     * Returns whether the transport has any lives left.
     */
    public function isAlive(): bool
    {
        return $this->livesLeft > 0;
    }

    /**
     * Takes one life from the transport.
     */
    public function reportDeath(): void
    {
        $this->livesLeft -= 1;
    }
}
