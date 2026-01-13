<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Strategies;

use ArtisanBuild\Pusher\Errors\TransportClosed;
use ArtisanBuild\Pusher\Errors\TransportPriorityTooLow;
use ArtisanBuild\Pusher\Errors\UnsupportedStrategy;
use ArtisanBuild\Pusher\Transports\Transport;
use ArtisanBuild\Pusher\Transports\TransportConnectionOptions;
use ArtisanBuild\Pusher\Utils\Collections;
use ArtisanBuild\Pusher\Utils\Factory;
use Closure;
use Exception;

/**
 * Provides a strategy interface for transports.
 */
class TransportStrategy implements Strategy
{
    public function __construct(
        public string $name,
        public int $priority,
        public Transport $transport,
        public StrategyOptions $options = new StrategyOptions,
    ) {}

    /**
     * Returns whether the transport is supported.
     */
    public function isSupported(): bool
    {
        return $this->transport->isSupported(new TransportConnectionOptions(
            useTLS: $this->options->useTLS,
        ));
    }

    /**
     * Launches a connection attempt and returns a strategy runner.
     */
    public function connect(int $minPriority, Closure $callback): StrategyRunner
    {
        if (! $this->isSupported()) {
            return self::failAttempt(new UnsupportedStrategy, $callback);
        }

        if ($this->priority < $minPriority) {
            return self::failAttempt(new TransportPriorityTooLow, $callback);
        }

        $connected = false;
        $transport = $this->transport->createConnection(
            $this->name,
            $this->priority,
            $this->options->key,
            $this->options
        );
        $handshake = null;
        $unbindListeners = null;

        $onInitialized = function () use ($transport, &$onInitialized): void {
            $transport->unbind('initialized', $onInitialized);
            $transport->connect();
        };

        $onOpen = function () use ($transport, $callback, &$connected, &$handshake, &$unbindListeners): void {
            $handshake = Factory::createHandshake($transport, function (mixed $result) use ($callback, &$connected, &$unbindListeners): void {
                $connected = true;
                $unbindListeners();
                $callback(null, $result);
            });
        };

        $onError = function (mixed $error) use ($callback, &$unbindListeners): void {
            $unbindListeners();
            $callback($error);
        };

        $onClosed = function () use ($transport, $callback, &$unbindListeners): void {
            $unbindListeners();
            $serializedTransport = Collections::safeJSONStringify($transport);
            $callback(new TransportClosed($serializedTransport));
        };

        $unbindListeners = function () use ($transport, &$onInitialized, &$onOpen, &$onError, &$onClosed): void {
            $transport->unbind('initialized', $onInitialized);
            $transport->unbind('open', $onOpen);
            $transport->unbind('error', $onError);
            $transport->unbind('closed', $onClosed);
        };

        $transport->bind('initialized', $onInitialized);
        $transport->bind('open', $onOpen);
        $transport->bind('error', $onError);
        $transport->bind('closed', $onClosed);

        // connect will be called automatically after initialization
        $transport->initialize();

        $strategy = $this;

        return new class($connected, $unbindListeners, $handshake, $transport, $strategy) implements StrategyRunner
        {
            public function __construct(
                private bool &$connected,
                private Closure $unbindListeners,
                private mixed &$handshake,
                private mixed $transport,
                private TransportStrategy $strategy,
            ) {}

            public function abort(): void
            {
                if ($this->connected) {
                    return;
                }
                ($this->unbindListeners)();
                if ($this->handshake) {
                    $this->handshake->close();
                } else {
                    $this->transport->close();
                }
            }

            public function forceMinPriority(int $p): void
            {
                if ($this->connected) {
                    return;
                }
                if ($this->strategy->priority < $p) {
                    if ($this->handshake) {
                        $this->handshake->close();
                    } else {
                        $this->transport->close();
                    }
                }
            }
        };
    }

    public static function failAttempt(Exception $error, Closure $callback): StrategyRunner
    {
        // Defer the callback execution
        // Util::defer(fn() => $callback($error));
        $callback($error);

        return new class implements StrategyRunner
        {
            public function abort(): void {}

            public function forceMinPriority(int $number): void {}
        };
    }
}
