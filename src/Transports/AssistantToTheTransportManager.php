<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

/**
 * Creates transport connections monitored by a transport manager.
 *
 * When a transport is closed, it might mean the environment does not support
 * it. It's possible that messages get stuck in an intermediate buffer or
 * proxies terminate inactive connections. To combat these problems,
 * assistants monitor the connection lifetime, report unclean exits and
 * adjust ping timeouts to keep the connection active. The decision to disable
 * a transport is the manager's responsibility.
 */
class AssistantToTheTransportManager
{
    public TransportManager $manager;

    public Transport $transport;

    public ?int $minPingDelay;

    public ?int $maxPingDelay;

    public ?int $pingDelay = null;

    public function __construct(
        TransportManager $manager,
        Transport $transport,
        PingDelayOptions $options
    ) {
        $this->manager = $manager;
        $this->transport = $transport;
        $this->minPingDelay = $options->minPingDelay;
        $this->maxPingDelay = $options->maxPingDelay;
    }

    /**
     * Creates a transport connection.
     *
     * This function has the same API as Transport#createConnection.
     */
    public function createConnection(
        string $name,
        int $priority,
        string $key,
        TransportConnectionOptions $options
    ): TransportConnection {
        $options->activityTimeout = $this->pingDelay;

        $connection = $this->transport->createConnection(
            $name,
            $priority,
            $key,
            $options
        );

        $openTimestamp = null;

        $onOpen = function () use ($connection, &$onOpen, &$onClosed, &$openTimestamp): void {
            $connection->unbind('open', $onOpen);
            $connection->bind('closed', $onClosed);
            $openTimestamp = $this->now();
        };

        $onClosed = function (mixed $closeEvent) use ($connection, &$onClosed, &$openTimestamp): void {
            $connection->unbind('closed', $onClosed);

            $code = $closeEvent['code'] ?? null;
            $wasClean = $closeEvent['wasClean'] ?? false;

            if ($code === 1002 || $code === 1003) {
                // we don't want to use transports not obeying the protocol
                $this->manager->reportDeath();
            } elseif (! $wasClean && $openTimestamp) {
                // report deaths only for short-living transport
                $lifespan = $this->now() - $openTimestamp;
                if ($lifespan < 2 * $this->maxPingDelay) {
                    $this->manager->reportDeath();
                    $this->pingDelay = max((int) ($lifespan / 2), $this->minPingDelay);
                }
            }
        };

        $connection->bind('open', $onOpen);

        return $connection;
    }

    /**
     * Returns whether the transport is supported in the environment.
     *
     * This function has the same API as Transport#isSupported. Might return false
     * when the manager decides to kill the transport.
     */
    public function isSupported(mixed $environment): bool
    {
        return $this->manager->isAlive() && $this->transport->isSupported($environment);
    }

    private function now(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
