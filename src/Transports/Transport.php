<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

/**
 * Provides interface for transport connection instantiation.
 *
 * Takes transport-specific hooks as the only argument, which allow checking
 * for transport support and creating its connections.
 */
class Transport
{
    public TransportHooks $hooks;

    public function __construct(TransportHooks $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * Returns whether the transport is supported in the environment.
     */
    public function isSupported(mixed $environment): bool
    {
        return $this->hooks->isSupported($environment);
    }

    /**
     * Creates a transport connection.
     */
    public function createConnection(
        string $name,
        int $priority,
        string $key,
        mixed $options
    ): TransportConnection {
        return new TransportConnection($this->hooks, $name, $priority, $key, $options);
    }
}
