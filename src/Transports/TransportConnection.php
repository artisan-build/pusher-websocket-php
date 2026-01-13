<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use ArtisanBuild\Pusher\Events\Dispatcher;
use ArtisanBuild\Pusher\Logger;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use ArtisanBuild\Pusher\Timeline\Timeline;
use Closure;

/**
 * Provides universal API for transport connections.
 *
 * Transport connection is a low-level object that wraps a connection method
 * and exposes a simple evented interface for the connection state and
 * messaging. It does not implement Pusher-specific WebSocket protocol.
 *
 * States:
 * - new - initial state after constructing the object
 * - initializing - during initialization phase, usually fetching resources
 * - initialized - ready to establish a connection
 * - connecting - when connection is being established
 * - open - when connection ready to be used
 * - closed - after connection was closed by either side
 */
class TransportConnection extends Dispatcher
{
    public TransportHooks $hooks;

    public string $name;

    public int $priority;

    public string $key;

    public TransportConnectionOptions $options;

    public string $state;

    public ?Timeline $timeline;

    public ?int $activityTimeout;

    public int $id;

    public mixed $socket = null;

    public ?Closure $beforeOpen = null;

    public ?Closure $initialize = null;

    public function __construct(
        TransportHooks $hooks,
        string $name,
        int $priority,
        string $key,
        TransportConnectionOptions $options
    ) {
        parent::__construct();
        // $this->initialize = Runtime::transportConnectionInitializer();
        $this->hooks = $hooks;
        $this->name = $name;
        $this->priority = $priority;
        $this->key = $key;
        $this->options = $options;

        $this->state = 'new';
        $this->timeline = $options->timeline;
        $this->activityTimeout = $options->activityTimeout;
        $this->id = $this->timeline?->generateUniqueID() ?? 0;
    }

    /**
     * Checks whether the transport handles activity checks by itself.
     */
    public function handlesActivityChecks(): bool
    {
        return $this->hooks->handlesActivityChecks();
    }

    /**
     * Checks whether the transport supports the ping/pong API.
     */
    public function supportsPing(): bool
    {
        return $this->hooks->supportsPing();
    }

    /**
     * Tries to establish a connection.
     */
    public function connect(): bool
    {
        if ($this->socket || $this->state !== 'initialized') {
            return false;
        }

        $url = $this->hooks->getUrls()->getInitial($this->key, $this->options);
        try {
            $this->socket = $this->hooks->getSocket($url, $this->options);
        } catch (\Throwable $e) {
            // Defer error handling
            $this->onError($e);
            $this->changeState('closed');

            return false;
        }

        $this->bindListeners();

        Logger::debug('Connecting', ['transport' => $this->name, 'url' => $url]);
        $this->changeState('connecting');

        return true;
    }

    /**
     * Closes the connection.
     */
    public function close(): bool
    {
        if ($this->socket) {
            $this->socket->close();

            return true;
        }

        return false;
    }

    /**
     * Sends data over the open connection.
     */
    public function send(mixed $data): bool
    {
        if ($this->state === 'open') {
            if ($this->socket) {
                $this->socket->send($data);
            }

            return true;
        }

        return false;
    }

    /**
     * Sends a ping if the connection is open and transport supports it.
     */
    public function ping(): void
    {
        if ($this->state === 'open' && $this->supportsPing()) {
            $this->socket->ping();
        }
    }

    private function onOpen(): void
    {
        if ($this->hooks->beforeOpen !== null) {
            $path = $this->hooks->getUrls()->getPath($this->key, $this->options);
            $this->hooks->beforeOpen($this->socket, $path ?? '');
        }
        $this->changeState('open');
        if ($this->socket) {
            $this->socket->onopen = null;
        }
    }

    private function onError(mixed $error): void
    {
        $this->emit('error', ['type' => 'WebSocketError', 'error' => $error]);
        $this->timeline?->error($this->buildTimelineMessage(['error' => (string) $error]));
    }

    private function onClose(?array $closeEvent = null): void
    {
        if ($closeEvent) {
            $this->changeState('closed', [
                'code' => $closeEvent['code'] ?? null,
                'reason' => $closeEvent['reason'] ?? null,
                'wasClean' => $closeEvent['wasClean'] ?? null,
            ]);
        } else {
            $this->changeState('closed');
        }
        $this->unbindListeners();
        $this->socket = null;
    }

    private function onMessage(mixed $message): void
    {
        $this->emit('message', $message);
    }

    private function onActivity(): void
    {
        $this->emit('activity');
    }

    private function bindListeners(): void
    {
        if (! $this->socket) {
            return;
        }

        $this->socket->onopen = function (): void {
            $this->onOpen();
        };
        $this->socket->onerror = function (mixed $error): void {
            $this->onError($error);
        };
        $this->socket->onclose = function (mixed $closeEvent): void {
            $this->onClose($closeEvent);
        };
        $this->socket->onmessage = function (mixed $message): void {
            $this->onMessage($message);
        };

        if ($this->supportsPing()) {
            $this->socket->onactivity = function (): void {
                $this->onActivity();
            };
        }
    }

    private function unbindListeners(): void
    {
        if ($this->socket) {
            $this->socket->onopen = null;
            $this->socket->onerror = null;
            $this->socket->onclose = null;
            $this->socket->onmessage = null;
            if ($this->supportsPing()) {
                $this->socket->onactivity = null;
            }
        }
    }

    private function changeState(string $state, ?array $params = null): void
    {
        $this->state = $state;
        $this->timeline?->info(
            $this->buildTimelineMessage([
                'state' => $state,
                'params' => $params,
            ])
        );
        $this->emit($state, $params);
    }

    public function buildTimelineMessage(array $message): array
    {
        return array_merge(['cid' => $this->id], $message);
    }
}
